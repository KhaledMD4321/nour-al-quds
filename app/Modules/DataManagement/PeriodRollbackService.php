<?php

namespace App\Modules\DataManagement;

use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Receipt;
use App\Models\Payment;
use App\Models\JournalEntry;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodRollbackService
{
    /**
     * قفل فترة مالية
     */
    public function lockPeriod(int $periodId, int $userId): FiscalPeriod
    {
        $period = FiscalPeriod::findOrFail($periodId);

        if ($period->is_locked) {
            throw new \RuntimeException('الفترة مقفولة مسبقاً');
        }

        $period->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        Log::info('Fiscal period locked', ['period_id' => $periodId, 'user_id' => $userId]);

        return $period->refresh();
    }

    /**
     * فتح فترة مالية مقفولة
     */
    public function unlockPeriod(int $periodId, int $userId): FiscalPeriod
    {
        $period = FiscalPeriod::findOrFail($periodId);

        if (! $period->is_locked) {
            throw new \RuntimeException('الفترة ليست مقفولة');
        }

        $period->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        Log::info('Fiscal period unlocked', ['period_id' => $periodId, 'user_id' => $userId]);

        return $period->refresh();
    }

    /**
     * معاينة ما سيتم التراجع عنه
     */
    public function previewRollback(string $fromDate, string $toDate): array
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->endOfDay();

        return [
            'invoices'  => Invoice::whereBetween('invoice_date', [$from, $to])->count(),
            'purchases' => PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->count(),
            'receipts'  => Receipt::whereBetween('receipt_date', [$from, $to])->count(),
            'payments'  => Payment::whereBetween('payment_date', [$from, $to])->count(),
            'entries'   => JournalEntry::whereBetween('entry_date', [$from, $to])->count(),
        ];
    }

    /**
     * Rollback كل مدخلات فترة زمنية
     * ⚠️ خطيرة جداً — تحتاج Super Admin + تأكيد مزدوج
     */
    public function rollback(string $fromDate, string $toDate, int $userId): array
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->endOfDay();

        $deleted = [
            'invoices'  => 0,
            'purchases' => 0,
            'receipts'  => 0,
            'payments'  => 0,
            'entries'   => 0,
        ];

        DB::transaction(function () use ($from, $to, $userId, &$deleted) {

            // ── 1. فواتير المبيعات (soft delete) ──
            $invoices = Invoice::whereBetween('invoice_date', [$from, $to])->get();
            foreach ($invoices as $inv) {
                // عكس حركات المخزون
                $this->reverseInvoiceStock($inv);
                $inv->delete();
                $deleted['invoices']++;
            }

            // ── 2. فواتير المشتريات ──
            $purchases = PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->get();
            foreach ($purchases as $inv) {
                $this->reversePurchaseStock($inv);
                $inv->delete();
                $deleted['purchases']++;
            }

            // ── 3. سندات القبض — عكس الخزينة ──
            $receipts = Receipt::whereBetween('receipt_date', [$from, $to])->get();
            foreach ($receipts as $r) {
                $this->reverseTreasuryIn($r->treasury_id, $r->amount, 'receipt', $r->id);
                $r->delete();
                $deleted['receipts']++;
            }

            // ── 4. سندات الصرف ──
            $payments = Payment::whereBetween('payment_date', [$from, $to])->get();
            foreach ($payments as $p) {
                $this->reverseTreasuryOut($p->treasury_id, $p->amount, 'payment', $p->id);
                $p->delete();
                $deleted['payments']++;
            }

            // ── 5. القيود المحاسبية ──
            $entries = JournalEntry::whereBetween('entry_date', [$from, $to])->get();
            foreach ($entries as $e) {
                $e->lines()->delete();
                $e->delete();
                $deleted['entries']++;
            }

        });

        Log::warning('Period rollback executed', [
            'from'    => $fromDate,
            'to'      => $toDate,
            'user_id' => $userId,
            'deleted' => $deleted,
        ]);

        return $deleted;
    }

    // ── private helpers ────────────────────────────────────────────────

    private function reverseInvoiceStock(Invoice $invoice): void
    {
        // نقوم بإعادة المخزون للصادر (فواتير بيع)
        foreach ($invoice->items ?? [] as $item) {
            $stock = Stock::where('warehouse_id', $invoice->warehouse_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($stock && $invoice->type === 'sale') {
                // البيع أنقص المخزون — نعيده
                $stock->increment('quantity', $item->quantity);
            } elseif ($stock && $invoice->type === 'sale_return') {
                // المرتجع زاد المخزون — ننقصه
                $stock->decrement('quantity', $item->quantity);
            }
        }
    }

    private function reversePurchaseStock(PurchaseInvoice $invoice): void
    {
        foreach ($invoice->items ?? [] as $item) {
            $stock = Stock::where('warehouse_id', $invoice->warehouse_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->decrement('quantity', $item->quantity);
            }
        }
    }

    private function reverseTreasuryIn(int $treasuryId, float $amount, string $refType, int $refId): void
    {
        $treasury = Treasury::lockForUpdate()->find($treasuryId);
        if (!$treasury) return;

        $newBalance = $treasury->current_balance - $amount;
        $treasury->update(['current_balance' => $newBalance]);

        TreasuryTransaction::create([
            'treasury_id'    => $treasuryId,
            'type'           => 'out',
            'amount'         => $amount,
            'balance_after'  => $newBalance,
            'reference_type' => 'rollback_' . $refType,
            'reference_id'   => $refId,
            'description'    => 'عكس ' . $refType . ' #' . $refId . ' — Rollback',
            'created_by'     => auth()->id(),
        ]);
    }

    private function reverseTreasuryOut(int $treasuryId, float $amount, string $refType, int $refId): void
    {
        $treasury = Treasury::lockForUpdate()->find($treasuryId);
        if (!$treasury) return;

        $newBalance = $treasury->current_balance + $amount;
        $treasury->update(['current_balance' => $newBalance]);

        TreasuryTransaction::create([
            'treasury_id'    => $treasuryId,
            'type'           => 'in',
            'amount'         => $amount,
            'balance_after'  => $newBalance,
            'reference_type' => 'rollback_' . $refType,
            'reference_id'   => $refId,
            'description'    => 'عكس ' . $refType . ' #' . $refId . ' — Rollback',
            'created_by'     => auth()->id(),
        ]);
    }
}
