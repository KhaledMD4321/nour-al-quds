<?php

namespace App\Modules\Sales;

use App\Models\Customer;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\UnitTransfer;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitTransferService
{
    /**
     * تأكيد التحويل بين الوحدتين — يولّد تلقائياً:
     * 1. فاتورة بيع داخلية من الوحدة المصدر
     * 2. فاتورة شراء داخلية للوحدة الوجهة
     * 3. حركات مخزون (transfer_out من المصدر + transfer_in للوجهة)
     */
    public function confirmTransfer(UnitTransfer $transfer): void
    {
        if (! $transfer->isDraft()) {
            throw new Exception('وثيقة التحويل مش في حالة مسودة');
        }

        if ($transfer->from_business_unit_id === $transfer->to_business_unit_id) {
            throw new Exception('الوحدة المصدر والوجهة لا يمكن أن تكونا نفس الوحدة');
        }

        $this->checkFiscalPeriod($transfer->transfer_date->toDateString());

        $transfer->load('items.product', 'fromBusinessUnit', 'toBusinessUnit');

        if ($transfer->items->isEmpty()) {
            throw new Exception('لازم تضيف أصناف قبل تأكيد التحويل');
        }

        // ── فحص أرصدة المخزن المصدر ──────────────────────────────────────────────
        foreach ($transfer->items as $item) {
            $available = (float) (Stock::where('warehouse_id', $transfer->from_warehouse_id)
                ->where('product_id', $item->product_id)
                ->value('quantity') ?? 0);

            if ($available < (float) $item->quantity) {
                $name = $item->product?->name ?? '#'.$item->product_id;
                throw new Exception(
                    "الكمية المطلوبة للصنف \"{$name}\" ({$item->quantity}) ".
                    "أكبر من المتاح في مخزن المصدر ({$available})"
                );
            }
        }

        DB::transaction(function () use ($transfer) {

            // ── 1. فاتورة البيع الداخلية ──────────────────────────────────────────
            $saleInvoice = $this->createInternalSaleInvoice($transfer);

            // ── 2. فاتورة الشراء الداخلية ─────────────────────────────────────────
            $purchaseInvoice = $this->createInternalPurchaseInvoice($transfer);

            // ── 3. حركات المخزون ──────────────────────────────────────────────────
            foreach ($transfer->items as $item) {
                $qty = (float) $item->quantity;
                $unitPrice = (float) $item->unit_price;

                // خصم من مخزن المصدر
                $fromStock = Stock::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $fromBalance = (float) $fromStock->quantity - $qty;
                $fromStock->update(['quantity' => $fromBalance, 'last_updated' => now()]);

                StockMovement::create([
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'transfer_out',
                    'quantity' => $qty,
                    'unit_cost' => (float) $fromStock->avg_cost,
                    'balance_after' => $fromBalance,
                    'reference_type' => 'unit_transfer',
                    'reference_id' => $transfer->id,
                    'notes' => 'تحويل صادر — '.$transfer->reference_number.
                                       ' → '.$transfer->toBusinessUnit->name,
                    'created_by' => Auth::id(),
                ]);

                // إضافة لمخزن الوجهة
                $toStock = Stock::where('warehouse_id', $transfer->to_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $oldQty = $toStock ? (float) $toStock->quantity : 0;
                $oldAvgCost = $toStock ? (float) $toStock->avg_cost : 0;
                $toBalance = $oldQty + $qty;

                // متوسط تكلفة مرجَّح
                $newAvgCost = $toBalance > 0
                    ? (($oldQty * $oldAvgCost) + ($qty * $unitPrice)) / $toBalance
                    : $unitPrice;

                if ($toStock) {
                    $toStock->update([
                        'quantity' => $toBalance,
                        'avg_cost' => round($newAvgCost, 4),
                        'last_updated' => now(),
                    ]);
                } else {
                    Stock::create([
                        'warehouse_id' => $transfer->to_warehouse_id,
                        'product_id' => $item->product_id,
                        'quantity' => $toBalance,
                        'avg_cost' => round($newAvgCost, 4),
                        'min_quantity' => 0,
                        'last_updated' => now(),
                    ]);
                }

                StockMovement::create([
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'transfer_in',
                    'quantity' => $qty,
                    'unit_cost' => $unitPrice,
                    'balance_after' => $toBalance,
                    'reference_type' => 'unit_transfer',
                    'reference_id' => $transfer->id,
                    'notes' => 'تحويل وارد — '.$transfer->reference_number.
                                       ' ← '.$transfer->fromBusinessUnit->name,
                    'created_by' => Auth::id(),
                ]);
            }

            // ── 4. تحديث وثيقة التحويل ───────────────────────────────────────────
            $transfer->update([
                'status' => 'confirmed',
                'sale_invoice_id' => $saleInvoice->id,
                'purchase_invoice_id' => $purchaseInvoice->id,
                'total_amount' => round($transfer->items->sum('total'), 2),
            ]);
        });
    }

    /** إعادة حساب إجمالي وثيقة التحويل */
    public function recalculateTotals(UnitTransfer $transfer): void
    {
        $total = $transfer->items()->sum('total');
        $transfer->update(['total_amount' => round((float) $total, 2)]);
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Private — إنشاء الفواتير الداخلية
    // ══════════════════════════════════════════════════════════════════════════════

    private function createInternalSaleInvoice(UnitTransfer $transfer): Invoice
    {
        $internalCustomer = $this->getOrCreateInternalCustomer(
            $transfer->toBusinessUnit->name,
            $transfer->to_business_unit_id
        );

        $subtotal = round($transfer->items->sum('total'), 2);

        $invoice = Invoice::create([
            'type' => 'sale',
            'reference_number' => Invoice::generateReference(),
            'business_unit_id' => $transfer->from_business_unit_id,
            'warehouse_id' => $transfer->from_warehouse_id,
            'customer_id' => $internalCustomer->id,
            'invoice_date' => $transfer->transfer_date,
            'status' => 'confirmed',
            'payment_type' => 'internal',
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $subtotal,
            'paid_amount' => $subtotal, // داخلي = مدفوع تلقائياً
            'notes' => 'تحويل داخلي — '.$transfer->reference_number,
            'created_by' => Auth::id(),
        ]);

        foreach ($transfer->items as $item) {
            $invoice->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'list_price' => $item->unit_price,
                'discount_1' => 0,
                'discount_2' => 0,
                'discount_3' => 0,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ]);
        }

        return $invoice;
    }

    private function createInternalPurchaseInvoice(UnitTransfer $transfer): PurchaseInvoice
    {
        $internalSupplier = $this->getOrCreateInternalSupplier(
            $transfer->fromBusinessUnit->name,
            $transfer->from_business_unit_id
        );

        $subtotal = round($transfer->items->sum('total'), 2);

        $purchaseInvoice = PurchaseInvoice::create([
            'reference_number' => PurchaseInvoice::generateReference(),
            'supplier_id' => $internalSupplier->id,
            'warehouse_id' => $transfer->to_warehouse_id,
            'business_unit_id' => $transfer->to_business_unit_id,
            'invoice_number' => $transfer->reference_number,
            'invoice_date' => $transfer->transfer_date,
            'status' => 'confirmed',
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'total_landed_cost' => 0,
            'total_amount' => $subtotal,
            'paid_amount' => $subtotal, // داخلي = مدفوع تلقائياً
            'notes' => 'تحويل داخلي — '.$transfer->reference_number,
            'created_by' => Auth::id(),
        ]);

        foreach ($transfer->items as $item) {
            $purchaseInvoice->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_price,
                'total' => $item->total,
                'landed_cost_share' => 0,
                'avg_cost_after' => $item->unit_price,
            ]);
        }

        return $purchaseInvoice;
    }

    // ── عميل داخلي (يُنشأ مرة واحدة لكل وحدة) ────────────────────────────────────

    private function getOrCreateInternalCustomer(string $unitName, int $businessUnitId): Customer
    {
        $name = '[داخلي] '.$unitName;

        return Customer::firstOrCreate(
            ['name' => $name],
            [
                'type' => 'internal',
                'business_unit_id' => $businessUnitId,
                'credit_limit' => 0,
                'default_discount_1' => 0,
                'default_discount_2' => 0,
                'default_discount_3' => 0,
                'opening_balance' => 0,
                'is_active' => true,
            ]
        );
    }

    // ── مورد داخلي (يُنشأ مرة واحدة لكل وحدة) ────────────────────────────────────

    private function getOrCreateInternalSupplier(string $unitName, int $businessUnitId): Supplier
    {
        $name = '[داخلي] '.$unitName;

        return Supplier::firstOrCreate(
            ['name' => $name],
            [
                'is_active' => true,
            ]
        );
    }

    // ── فحص الفترة المالية ────────────────────────────────────────────────────────

    private function checkFiscalPeriod(string $date): void
    {
        $period = FiscalPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($period && $period->is_locked) {
            throw new Exception('الفترة المالية مقفولة — لا يمكن تسجيل معاملات فيها');
        }
    }
}
