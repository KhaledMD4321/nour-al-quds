<?php

namespace App\Modules\Finance;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Receipt;
use App\Models\Treasury;
use Illuminate\Support\Facades\DB;

class ReceiptService
{
    /**
     * CoA codes
     *   1111 = خزينة نقدية المعرض
     *   1112 = خزينة نقدية التوزيع
     *   1113 = البنك (مشترك)
     *   1114 = بنك المعرض
     *   1115 = بنك التوزيع
     *   1121 = عملاء المعرض
     *   1122 = عملاء التوزيع
     *   1130 = شيكات تحت التحصيل
     */
    private const ACCOUNTS = [
        'cash'  => [1 => 1111, 2 => 1112],
        'bank'  => [1 => 1114, 2 => 1115],
        'cheques' => 1130,
        'receivables' => [1 => 1121, 2 => 1122],
    ];

    /**
     * إنشاء إيصال تحصيل جديد
     *
     * @param  array{
     *   customer_id: int,
     *   invoice_id: int|null,
     *   treasury_id: int|null,
     *   business_unit_id: int,
     *   amount: float,
     *   payment_method: string,
     *   receipt_date: string,
     *   cheque_details: array|null,
     *   bank_reference: string|null,
     *   notes: string|null,
     *   created_by: int,
     * } $data
     */
    public function createReceipt(array $data): Receipt
    {
        return DB::transaction(function () use ($data) {

            // 0. توليد رقم الإيصال مرة واحدة
            $receiptNumber = Receipt::generateReceiptNumber();

            // 1. تحقق من الفاتورة إن وُجدت
            $invoice = null;
            if (!empty($data['invoice_id'])) {
                $invoice = Invoice::lockForUpdate()->findOrFail($data['invoice_id']);

                if ($invoice->isFullyPaid()) {
                    throw new \RuntimeException('هذه الفاتورة مدفوعة بالكامل بالفعل.');
                }

                $remaining = $invoice->remaining_amount;
                if ((float) $data['amount'] > $remaining + 0.005) {
                    throw new \RuntimeException(
                        sprintf('المبلغ المدخل (%.2f) يتجاوز المتبقي على الفاتورة (%.2f).', $data['amount'], $remaining)
                    );
                }
            }

            // 2. إضافة الأموال للخزينة (نقدي / بنك)
            $treasuryTx = null;
            if ($data['payment_method'] !== 'cheque') {
                $treasury = Treasury::lockForUpdate()->findOrFail($data['treasury_id']);
                $treasury->increment('current_balance', $data['amount']);

                // تسجيل treasury_transaction
                $treasuryTx = $treasury->transactions()->create([
                    'type'             => 'receipt',
                    'amount'           => $data['amount'],
                    'balance_after'    => $treasury->fresh()->current_balance,
                    'transaction_date' => $data['receipt_date'],
                    'description'      => 'تحصيل إيصال رقم ' . $receiptNumber,
                    'reference_type'   => Receipt::class,
                    'reference_id'     => null, // سيُحدَّث بعد الإنشاء
                    'created_by'       => $data['created_by'],
                ]);
            }

            // 3. قيد محاسبي
            $journalEntry = $this->buildJournalEntry($data);

            // 4. إنشاء الإيصال
            $receipt = Receipt::create([
                'receipt_number'   => $receiptNumber,
                'treasury_id'      => $data['payment_method'] !== 'cheque' ? ($data['treasury_id'] ?? null) : null,
                'customer_id'      => $data['customer_id'],
                'invoice_id'       => $data['invoice_id'] ?? null,
                'business_unit_id' => $data['business_unit_id'],
                'amount'           => $data['amount'],
                'payment_method'   => $data['payment_method'],
                'receipt_date'     => $data['receipt_date'],
                'cheque_details'   => $data['cheque_details'] ?? null,
                'bank_reference'   => $data['bank_reference'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'journal_entry_id' => $journalEntry->id,
                'created_by'       => $data['created_by'],
            ]);

            // 5. ربط polymorphic في journal_entry source
            $journalEntry->update([
                'source_type' => Receipt::class,
                'source_id'   => $receipt->id,
            ]);

            // 5b. ربط treasury_transaction بالإيصال
            if ($treasuryTx) {
                $treasuryTx->update(['reference_id' => $receipt->id]);
            }

            // 6. تحديث حالة الفاتورة
            if ($invoice) {
                $invoice->refreshPaymentStatus();
            }

            return $receipt;
        });
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function buildJournalEntry(array $data): JournalEntry
    {
        $unitId    = (int) $data['business_unit_id'];
        $amount    = (float) $data['amount'];
        $method    = $data['payment_method'];

        // حدد حساب المدين (الطرف الذي يتلقى المال)
        $debitAccountCode = match ($method) {
            'cash'          => self::ACCOUNTS['cash'][$unitId]  ?? 1111,
            'bank_transfer' => self::ACCOUNTS['bank'][$unitId]  ?? 1113,
            'cheque'        => self::ACCOUNTS['cheques'],
        };

        // حدد حساب الدائن (عملاء الوحدة)
        $creditAccountCode = self::ACCOUNTS['receivables'][$unitId] ?? 1121;

        $debitAccount  = \App\Models\ChartOfAccount::where('code', $debitAccountCode)->firstOrFail();
        $creditAccount = \App\Models\ChartOfAccount::where('code', $creditAccountCode)->firstOrFail();

        $description = match ($method) {
            'cash'          => 'تحصيل نقدي',
            'bank_transfer' => 'تحويل بنكي',
            'cheque'        => 'شيك تحت التحصيل',
        };

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => $data['receipt_date'],
            'description'  => $description . ' من العميل رقم ' . $data['customer_id'],
            'source_type'  => Receipt::class, // سيُحدَّث بعد الإنشاء
            'source_id'    => null,
            'is_manual'    => false,
            'is_posted'    => true,
            'total_debit'  => $amount,
            'total_credit' => $amount,
            'created_by'   => $data['created_by'],
        ]);

        JournalEntryLine::insert([
            [
                'journal_entry_id' => $entry->id,
                'account_id'       => $debitAccount->id,
                'business_unit_id' => $unitId,
                'debit'            => $amount,
                'credit'           => 0,
                'description'      => $description,
                'created_at'       => now(),
            ],
            [
                'journal_entry_id' => $entry->id,
                'account_id'       => $creditAccount->id,
                'business_unit_id' => $unitId,
                'debit'            => 0,
                'credit'           => $amount,
                'description'      => 'تسوية ذمة عميل',
                'created_at'       => now(),
            ],
        ]);

        return $entry;
    }
}
