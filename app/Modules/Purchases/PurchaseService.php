<?php

namespace App\Modules\Purchases;

use App\Models\FiscalPeriod;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    // ── إنشاء فاتورة مشتريات جديدة (draft) ──────────────────────────────────

    public function createInvoice(array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($data) {
            return PurchaseInvoice::create([
                'reference_number' => PurchaseInvoice::generateReference(),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'business_unit_id' => $data['business_unit_id'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
        });
    }

    // ── تأكيد الفاتورة: تنقل المخزون + تحسب التكاليف ────────────────────────

    public function confirmInvoice(PurchaseInvoice $invoice): void
    {
        if (! $invoice->isDraft()) {
            throw new Exception('الفاتورة مش في حالة مسودة — مش ممكن تتأكد تاني');
        }

        $this->checkFiscalPeriod($invoice->invoice_date);

        if ($invoice->items()->count() === 0) {
            throw new Exception('الفاتورة لازم تحتوي على بند واحد على الأقل قبل التأكيد');
        }

        DB::transaction(function () use ($invoice) {

            // 1. توزيع الـ landed costs على البنود
            $this->distributeLandedCosts($invoice);

            // 2. تحديث المخزون لكل بند
            $invoice->items()->with('product')->each(function ($item) use ($invoice) {
                $this->increaseStock($item, $invoice);
            });

            // 3. حساب الإجماليات وتحديث الفاتورة
            $invoice->refresh();
            $subtotal = $invoice->items()->whereNull('purchase_invoice_items.deleted_at')->sum('total');
            $totalLanded = $invoice->landedCosts()->sum('amount');
            $taxAmount = (float) ($invoice->tax_amount ?? 0);
            $totalAmount = $subtotal + $taxAmount + $totalLanded;

            $invoice->update([
                'status' => 'confirmed',
                'subtotal' => $subtotal,
                'total_landed_cost' => $totalLanded,
                'total_amount' => $totalAmount,
            ]);
        });
    }

    // ── إعادة حساب إجمالي الفاتورة (تُستدعى بعد تعديل البنود) ──────────────

    public function recalculateTotals(PurchaseInvoice $invoice): void
    {
        $subtotal = $invoice->items()->whereNull('purchase_invoice_items.deleted_at')->sum('total');
        $taxAmount = (float) ($invoice->tax_amount ?? 0);
        $totalLanded = $invoice->landedCosts()->sum('amount');

        $invoice->update([
            'subtotal' => $subtotal,
            'total_landed_cost' => $totalLanded,
            'total_amount' => $subtotal + $taxAmount + $totalLanded,
        ]);
    }

    // ── توزيع الـ Landed Costs على البنود نسبياً حسب القيمة ─────────────────

    public function distributeLandedCosts(PurchaseInvoice $invoice): void
    {
        $totalLanded = (float) $invoice->landedCosts()->sum('amount');
        if ($totalLanded <= 0) {
            return;
        }

        $invoiceSubtotal = (float) $invoice->items()->whereNull('purchase_invoice_items.deleted_at')->sum('total');
        if ($invoiceSubtotal <= 0) {
            return;
        }

        $invoice->items()->whereNull('deleted_at')->each(function ($item) use ($totalLanded, $invoiceSubtotal) {
            $share = ((float) $item->total / $invoiceSubtotal) * $totalLanded;
            $item->update(['landed_cost_share' => round($share, 4)]);
        });
    }

    // ── زيادة المخزون لبند واحد ──────────────────────────────────────────────

    private function increaseStock(PurchaseInvoiceItem $item, PurchaseInvoice $invoice): void
    {
        // ★ lockForUpdate قبل أي تعديل على المخزون
        $stock = Stock::where('warehouse_id', $invoice->warehouse_id)
            ->where('product_id', $item->product_id)
            ->lockForUpdate()
            ->first();

        $oldQty = $stock ? (float) $stock->quantity : 0;
        $oldCost = $stock ? (float) $stock->avg_cost : 0;
        $newQty = (float) $item->quantity;
        $effectiveUnitCost = $item->effective_unit_cost;

        // حساب متوسط التكلفة المرجّح (Weighted Average Cost)
        $totalOldValue = $oldQty * $oldCost;
        $totalNewValue = $newQty * $effectiveUnitCost;
        $newAvgCost = ($oldQty + $newQty) > 0
            ? ($totalOldValue + $totalNewValue) / ($oldQty + $newQty)
            : $effectiveUnitCost;

        $balanceAfter = $oldQty + $newQty;

        if ($stock) {
            $stock->update([
                'quantity' => $balanceAfter,
                'avg_cost' => round($newAvgCost, 4),
                'last_updated' => now(),
            ]);
        } else {
            Stock::create([
                'warehouse_id' => $invoice->warehouse_id,
                'product_id' => $item->product_id,
                'quantity' => $balanceAfter,
                'avg_cost' => round($newAvgCost, 4),
                'last_updated' => now(),
            ]);
        }

        // ★ تسجيل حركة مخزون — سجل أبدي
        StockMovement::create([
            'warehouse_id' => $invoice->warehouse_id,
            'product_id' => $item->product_id,
            'type' => 'in',
            'quantity' => $newQty,
            'unit_cost' => round($effectiveUnitCost, 4),
            'balance_after' => $balanceAfter,
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => 'استلام بضاعة — '.$invoice->reference_number,
            'created_by' => $invoice->created_by,
        ]);

        // حفظ متوسط التكلفة في البند نفسه
        $item->update(['avg_cost_after' => round($newAvgCost, 4)]);
    }

    // ── فحص الفترة المالية ────────────────────────────────────────────────────

    private function checkFiscalPeriod($date): void
    {
        $period = FiscalPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($period && $period->is_locked) {
            throw new Exception('الفترة المالية مقفولة — لا يمكن تسجيل معاملات فيها');
        }
    }
}
