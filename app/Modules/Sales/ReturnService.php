<?php

namespace App\Modules\Sales;

use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\Stock;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    // ══════════════════════════════════════════════════════════════════════════════
    // مرتجع مبيعات
    // ══════════════════════════════════════════════════════════════════════════════

    /**
     * إنشاء مرتجع مبيعات من فاتورة أصلية
     *
     * $items = [
     *   ['invoice_item_id' => 1, 'quantity' => 2],
     *   ['invoice_item_id' => 3, 'quantity' => 1],
     * ]
     */
    public function createSaleReturn(
        Invoice $originalInvoice,
        array $items,
        ?string $notes = null
    ): Invoice {

        if ($originalInvoice->type !== 'sale') {
            throw new Exception('هذه الفاتورة مش فاتورة مبيعات');
        }

        if (! in_array($originalInvoice->status, ['confirmed', 'delivered', 'partially_paid', 'paid'])) {
            throw new Exception('لا يمكن عمل مرتجع لفاتورة غير مؤكدة');
        }

        $this->checkFiscalPeriod(now()->toDateString());

        // ── فحص الكميات قبل البدء ────────────────────────────────────────────────
        foreach ($items as $itemData) {
            $originalItem = InvoiceItem::find($itemData['invoice_item_id']);

            if (! $originalItem || $originalItem->invoice_id !== $originalInvoice->id) {
                throw new Exception('بند غير موجود في الفاتورة الأصلية');
            }

            $alreadyReturned = $this->getReturnedQuantity(
                $originalInvoice->id,
                $originalItem->product_id
            );

            $maxReturnable = (float) $originalItem->quantity - $alreadyReturned;

            if ((float) $itemData['quantity'] > $maxReturnable) {
                $name = $originalItem->product?->name ?? '#'.$originalItem->product_id;
                throw new Exception(
                    "الكمية المرتجعة للصنف \"{$name}\" ".
                    "({$itemData['quantity']}) أكبر من المسموح ({$maxReturnable})"
                );
            }
        }

        return DB::transaction(function () use ($originalInvoice, $items, $notes) {

            $totalAmount = 0;
            $returnItems = [];

            foreach ($items as $itemData) {
                $originalItem = InvoiceItem::find($itemData['invoice_item_id']);
                $qty = (float) $itemData['quantity'];
                $total = round($qty * (float) $originalItem->unit_price, 2);
                $totalAmount += $total;

                $returnItems[] = [
                    'original_item' => $originalItem,
                    'quantity' => $qty,
                    'total' => $total,
                ];
            }

            // ── فاتورة المرتجع ────────────────────────────────────────────────────
            $returnInvoice = Invoice::create([
                'type' => 'sale_return',
                'reference_number' => Invoice::generateReturnReference(),
                'business_unit_id' => $originalInvoice->business_unit_id,
                'warehouse_id' => $originalInvoice->warehouse_id,
                'customer_id' => $originalInvoice->customer_id,
                'invoice_date' => now()->toDateString(),
                'status' => 'confirmed',
                'payment_type' => $originalInvoice->payment_type,
                'subtotal' => $totalAmount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'notes' => $notes ?? ('مرتجع من '.$originalInvoice->reference_number),
                'original_invoice_id' => $originalInvoice->id,
                'created_by' => Auth::id(),
            ]);

            // ── البنود + إرجاع المخزون ────────────────────────────────────────────
            foreach ($returnItems as $ri) {
                $originalItem = $ri['original_item'];

                $returnInvoice->items()->create([
                    'product_id' => $originalItem->product_id,
                    'quantity' => $ri['quantity'],
                    'list_price' => $originalItem->list_price,
                    'discount_1' => $originalItem->discount_1,
                    'discount_2' => $originalItem->discount_2,
                    'discount_3' => $originalItem->discount_3,
                    'unit_price' => $originalItem->unit_price,
                    'total' => $ri['total'],
                ]);

                $this->increaseStock(
                    warehouseId: $originalInvoice->warehouse_id,
                    productId: $originalItem->product_id,
                    quantity: $ri['quantity'],
                    unitCost: (float) $originalItem->unit_price,
                    refType: 'sale_return',
                    refId: $returnInvoice->id,
                    notes: 'مرتجع مبيعات — '.$returnInvoice->reference_number,
                );
            }

            return $returnInvoice;
        });
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // مرتجع مشتريات
    // ══════════════════════════════════════════════════════════════════════════════

    public function confirmPurchaseReturn(PurchaseReturn $return): void
    {
        if (! $return->isDraft()) {
            throw new Exception('وثيقة المرتجع مش في حالة مسودة');
        }

        $this->checkFiscalPeriod($return->return_date->toDateString());

        $return->load('items.product', 'purchaseInvoice.items');

        if ($return->items->isEmpty()) {
            throw new Exception('لازم تضيف صنف واحد على الأقل');
        }

        // ── فحص الكميات ──────────────────────────────────────────────────────────
        foreach ($return->items as $item) {
            $originalItem = $return->purchaseInvoice->items()
                ->where('product_id', $item->product_id)
                ->first();

            if (! $originalItem) {
                $name = $item->product?->name ?? '#'.$item->product_id;
                throw new Exception("الصنف \"{$name}\" مش موجود في فاتورة الشراء الأصلية");
            }

            $alreadyReturned = $this->getPurchaseReturnedQuantity(
                $return->purchase_invoice_id,
                $item->product_id,
                $return->id
            );

            $maxReturnable = (float) $originalItem->quantity - $alreadyReturned;

            if ((float) $item->quantity > $maxReturnable) {
                $name = $item->product?->name ?? '#'.$item->product_id;
                throw new Exception(
                    "الكمية المرتجعة للصنف \"{$name}\" ".
                    "({$item->quantity}) أكبر من المسموح ({$maxReturnable})"
                );
            }
        }

        DB::transaction(function () use ($return) {

            $totalAmount = 0;

            foreach ($return->items as $item) {
                $stock = Stock::where('warehouse_id', $return->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $available = $stock ? (float) $stock->quantity : 0;
                $qty = (float) $item->quantity;

                if ($available < $qty) {
                    $name = $item->product?->name ?? '#'.$item->product_id;
                    throw new Exception(
                        "الكمية المتاحة للصنف \"{$name}\" ".
                        "({$available}) أقل من المرتجع ({$qty})"
                    );
                }

                $balanceAfter = $available - $qty;

                if ($stock) {
                    $stock->update(['quantity' => $balanceAfter, 'last_updated' => now()]);
                }

                StockMovement::create([
                    'warehouse_id' => $return->warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'quantity' => $qty,
                    'unit_cost' => (float) $item->unit_cost,
                    'balance_after' => $balanceAfter,
                    'reference_type' => 'purchase_return',
                    'reference_id' => $return->id,
                    'notes' => 'مرتجع مشتريات — '.$return->reference_number,
                    'created_by' => Auth::id(),
                ]);

                $totalAmount += (float) $item->total;
            }

            $return->update([
                'status' => 'confirmed',
                'total_amount' => round($totalAmount, 2),
            ]);
        });
    }

    public function recalculatePurchaseReturnTotals(PurchaseReturn $return): void
    {
        $total = $return->items()->sum(DB::raw('quantity * unit_cost'));
        $return->update(['total_amount' => round((float) $total, 2)]);
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Private Helpers
    // ══════════════════════════════════════════════════════════════════════════════

    private function increaseStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        float $unitCost,
        string $refType,
        int $refId,
        string $notes,
    ): void {
        $stock = Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        $currentQty = $stock ? (float) $stock->quantity : 0;
        $balanceAfter = $currentQty + $quantity;

        if ($stock) {
            $stock->update(['quantity' => $balanceAfter, 'last_updated' => now()]);
        } else {
            Stock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => $balanceAfter,
                'avg_cost' => $unitCost,
                'min_quantity' => 0,
                'last_updated' => now(),
            ]);
        }

        StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'balance_after' => $balanceAfter,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'notes' => $notes,
            'created_by' => Auth::id(),
        ]);
    }

    /** الكمية المرتجعة مسبقاً من فاتورة مبيعات لصنف معين */
    private function getReturnedQuantity(int $invoiceId, int $productId): float
    {
        return (float) Invoice::where('original_invoice_id', $invoiceId)
            ->where('type', 'sale_return')
            ->where('status', 'confirmed')
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoice_items.product_id', $productId)
            ->sum('invoice_items.quantity');
    }

    /** الكمية المرتجعة مسبقاً من فاتورة شراء لصنف معين */
    private function getPurchaseReturnedQuantity(
        int $purchaseInvoiceId,
        int $productId,
        ?int $excludeReturnId = null
    ): float {
        return (float) PurchaseReturn::where('purchase_invoice_id', $purchaseInvoiceId)
            ->where('status', 'confirmed')
            ->when($excludeReturnId, fn ($q) => $q->where('id', '!=', $excludeReturnId))
            ->join('purchase_return_items',
                'purchase_returns.id', '=',
                'purchase_return_items.purchase_return_id')
            ->where('purchase_return_items.product_id', $productId)
            ->sum('purchase_return_items.quantity');
    }

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
