<?php

namespace App\Modules\Sales;

use App\Models\Customer;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    // ── تأكيد الفاتورة ──────────────────────────────────────────────────────────

    /**
     * تأكيد الفاتورة: فحص الفترة المالية + الائتمان + خصم المخزون
     */
    public function confirmInvoice(Invoice $invoice): void
    {
        if (! $invoice->isDraft()) {
            throw new Exception('لا يمكن تأكيد فاتورة غير مسودة');
        }

        $this->checkFiscalPeriod(now()->toDateString());

        // فحص حد الائتمان للدفع الآجل
        if ($invoice->payment_type === 'credit') {
            $this->checkCreditLimit($invoice);
        }

        DB::transaction(function () use ($invoice) {
            $invoice->load('items');

            foreach ($invoice->items as $item) {
                $qty = (float) $item->quantity;

                // lockForUpdate قبل تعديل المخزون
                $stock = Stock::where('warehouse_id', $invoice->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $available = $stock ? (float) $stock->quantity : 0;

                if ($available < $qty) {
                    $productName = $item->product?->name ?? '#' . $item->product_id;
                    throw new Exception(
                        "الكمية المطلوبة للصنف \"{$productName}\" ({$qty}) أكبر من المتاح ({$available})"
                    );
                }

                $balanceAfter = $available - $qty;

                if ($stock) {
                    $stock->update([
                        'quantity'     => $balanceAfter,
                        'last_updated' => now(),
                    ]);
                }

                StockMovement::create([
                    'warehouse_id'   => $invoice->warehouse_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'out',
                    'quantity'       => $qty,
                    'unit_cost'      => $stock ? (float) $stock->avg_cost : 0,
                    'balance_after'  => $balanceAfter,
                    'reference_type' => Invoice::class,
                    'reference_id'   => $invoice->id,
                    'notes'          => 'فاتورة بيع — ' . $invoice->reference_number,
                    'created_by'     => Auth::id(),
                ]);
            }

            $invoice->update(['status' => 'confirmed']);
        });
    }

    // ── إلغاء الفاتورة ──────────────────────────────────────────────────────────

    /**
     * إلغاء الفاتورة: لو كانت مؤكدة/مسلّمة يتم إرجاع المخزون
     */
    public function cancelInvoice(Invoice $invoice): void
    {
        if ($invoice->isCancelled()) {
            throw new Exception('الفاتورة ملغاة بالفعل');
        }

        if (in_array($invoice->status, ['partially_paid', 'paid'])) {
            throw new Exception('لا يمكن إلغاء فاتورة مدفوعة — راجع المحاسبة');
        }

        DB::transaction(function () use ($invoice) {
            // لو كانت مؤكدة أو مسلّمة نرجع المخزون
            if ($invoice->isConfirmedOrBeyond()) {
                $this->increaseStockOnCancel($invoice);
            }

            $invoice->update(['status' => 'cancelled']);
        });
    }

    // ── إعادة حساب الإجماليات ────────────────────────────────────────────────────

    /**
     * تحديث subtotal + total_amount بناءً على بنود الفاتورة.
     * يُستدعى بعد أي تغيير على invoice_items.
     */
    public function recalculateTotals(Invoice $invoice): void
    {
        $invoice->load('items');

        $subtotal = $invoice->items->sum(fn ($item) => (float) $item->total);

        $totalAmount = round(
            $subtotal - (float) $invoice->discount_amount + (float) $invoice->tax_amount,
            2
        );

        $invoice->update([
            'subtotal'     => round($subtotal, 2),
            'total_amount' => $totalAmount,
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * فحص الفترة المالية المقفولة
     */
    private function checkFiscalPeriod(string $date): void
    {
        $period = FiscalPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($period && $period->is_locked) {
            throw new Exception('الفترة المالية مقفولة — لا يمكن تسجيل معاملات فيها');
        }
    }

    /**
     * فحص حد الائتمان للعميل
     */
    private function checkCreditLimit(Invoice $invoice): void
    {
        $customer = Customer::find($invoice->customer_id);
        if (! $customer) return;

        $creditLimit = (float) $customer->credit_limit;
        if ($creditLimit <= 0) return; // بدون حد ائتمان

        // المستحق الحالي (فواتير آجلة مؤكدة غير محصّلة بالكامل)
        $outstanding = Invoice::where('customer_id', $invoice->customer_id)
            ->whereIn('status', ['confirmed', 'delivered', 'partially_paid'])
            ->where('payment_type', 'credit')
            ->selectRaw('SUM(total_amount - paid_amount) as total_outstanding')
            ->value('total_outstanding') ?? 0;

        if (((float) $outstanding + (float) $invoice->total_amount) > $creditLimit) {
            throw new Exception(
                sprintf(
                    'تجاوز حد الائتمان للعميل — الحد: %s ج.م — المستحق: %s ج.م',
                    number_format($creditLimit, 2),
                    number_format($outstanding, 2)
                )
            );
        }
    }

    /**
     * إرجاع المخزون عند إلغاء فاتورة مؤكدة/مسلّمة
     */
    private function increaseStockOnCancel(Invoice $invoice): void
    {
        $invoice->load('items');

        foreach ($invoice->items as $item) {
            $qty = (float) $item->quantity;

            $stock = Stock::where('warehouse_id', $invoice->warehouse_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $newQty = (float) $stock->quantity + $qty;
                $stock->update([
                    'quantity'     => $newQty,
                    'last_updated' => now(),
                ]);

                StockMovement::create([
                    'warehouse_id'   => $invoice->warehouse_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'in',
                    'quantity'       => $qty,
                    'unit_cost'      => (float) $stock->avg_cost,
                    'balance_after'  => $newQty,
                    'reference_type' => Invoice::class,
                    'reference_id'   => $invoice->id,
                    'notes'          => 'إلغاء فاتورة — ' . $invoice->reference_number,
                    'created_by'     => Auth::id(),
                ]);
            } else {
                // المخزون غير موجود — ننشئه
                $newQty = $qty;
                $stock  = Stock::create([
                    'warehouse_id' => $invoice->warehouse_id,
                    'product_id'   => $item->product_id,
                    'quantity'     => $newQty,
                    'avg_cost'     => (float) $item->unit_price,
                    'last_updated' => now(),
                ]);

                StockMovement::create([
                    'warehouse_id'   => $invoice->warehouse_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'in',
                    'quantity'       => $qty,
                    'unit_cost'      => (float) $item->unit_price,
                    'balance_after'  => $newQty,
                    'reference_type' => Invoice::class,
                    'reference_id'   => $invoice->id,
                    'notes'          => 'إلغاء فاتورة — ' . $invoice->reference_number,
                    'created_by'     => Auth::id(),
                ]);
            }
        }
    }
}
