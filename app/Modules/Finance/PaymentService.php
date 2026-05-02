<?php

namespace App\Modules\Finance;

use App\Models\BusinessUnit;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Treasury;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PaymentService
{
    public function __construct(
        protected TreasuryService $treasuryService,
    ) {}

    /**
     * إنشاء سند صرف كامل
     *
     * @throws InvalidArgumentException  بيانات غير صحيحة
     * @throws RuntimeException          رصيد غير كافٍ أو فاتورة مدفوعة
     */
    public function create(array $data, int $createdBy): Payment
    {
        // fallback لـ business_unit_id
        $unitId = (int) ($data['business_unit_id'] ?? 0);
        if ($unitId === 0 && ! empty($data['treasury_id'])) {
            $unitId = (int) Treasury::find($data['treasury_id'])?->business_unit_id;
        }
        if ($unitId === 0) {
            $unitId = (int) auth()->user()?->business_unit_id;
        }
        $data['business_unit_id'] = $unitId;

        $this->validateData($data);

        return DB::transaction(function () use ($data, $createdBy) {

            // قفل فاتورة المشتريات لو مرتبطة
            $purchaseInvoice = null;
            if (! empty($data['purchase_invoice_id'])) {
                $purchaseInvoice = PurchaseInvoice::lockForUpdate()
                    ->findOrFail($data['purchase_invoice_id']);
                $this->validateInvoicePayment($purchaseInvoice, (float) $data['amount']);
            }

            // 1. إنشاء سند الصرف
            $payment = Payment::create([
                'payment_number'      => Payment::generateReference(),
                'treasury_id'         => $data['treasury_id'] ?? null,
                'supplier_id'         => $data['supplier_id'] ?? null,
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'business_unit_id'    => $data['business_unit_id'],
                'amount'              => $data['amount'],
                'category'            => $data['category'],
                'payment_method'      => $data['payment_method'],
                'payment_date'        => $data['payment_date'],
                'cheque_details'      => $data['cheque_details'] ?? null,
                'bank_reference'      => $data['bank_reference'] ?? null,
                'expense_account_id'  => $data['expense_account_id'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'created_by'          => $createdBy,
            ]);

            // 2. خصم من الخزينة (كاش أو تحويل بنكي فقط — الشيك لا يخصم)
            if (in_array($data['payment_method'], ['cash', 'bank_transfer'])) {
                $this->treasuryService->deductFunds(
                    treasuryId:      $data['treasury_id'],
                    amount:          (float) $data['amount'],
                    description:     $this->buildTreasuryDescription($payment),
                    referenceType:   Payment::class,
                    referenceId:     $payment->id,
                    createdBy:       $createdBy,
                    transactionDate: $data['payment_date'],
                );
            }

            // 3. توليد القيد المحاسبي
            $entry = $this->buildJournalEntry($payment);
            $payment->update(['journal_entry_id' => $entry->id]);

            // 4. تحديث paid_amount + status لفاتورة المشتريات
            if ($purchaseInvoice) {
                $purchaseInvoice->increment('paid_amount', (float) $data['amount']);
                $purchaseInvoice->refresh();
                $purchaseInvoice->refreshPaymentStatus();
            }

            return $payment->fresh(['supplier', 'purchaseInvoice', 'treasury', 'journalEntry']);
        });
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    private function validateData(array $data): void
    {
        if (empty($data['amount']) || (float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('المبلغ لازم يكون أكبر من صفر');
        }

        if (! in_array($data['payment_method'] ?? null, ['cash', 'cheque', 'bank_transfer'])) {
            throw new InvalidArgumentException('طريقة الدفع غير صحيحة');
        }

        // دفع مورد → supplier_id إلزامي
        if (($data['category'] ?? '') === 'supplier_payment' && empty($data['supplier_id'])) {
            throw new InvalidArgumentException('لازم تختار المورد لدفع المورد');
        }

        // مصروف تشغيلي → expense_account_id إلزامي
        if (($data['category'] ?? '') !== 'supplier_payment' && empty($data['expense_account_id'])) {
            throw new InvalidArgumentException('لازم تختار حساب المصروف');
        }

        // كاش أو تحويل → خزينة إلزامية
        if (in_array($data['payment_method'], ['cash', 'bank_transfer']) && empty($data['treasury_id'])) {
            throw new InvalidArgumentException('لازم تختار خزينة لطريقة الدفع دي');
        }

        // كاش → خزينة نقدية فقط
        if ($data['payment_method'] === 'cash' && ! empty($data['treasury_id'])) {
            $t = Treasury::findOrFail($data['treasury_id']);
            if ($t->type !== 'cash') {
                throw new InvalidArgumentException('الكاش لازم يخرج من خزينة نقدية مش بنك');
            }
        }

        // تحويل بنكي → خزينة بنك فقط
        if ($data['payment_method'] === 'bank_transfer' && ! empty($data['treasury_id'])) {
            $t = Treasury::findOrFail($data['treasury_id']);
            if ($t->type !== 'bank') {
                throw new InvalidArgumentException('التحويل البنكي لازم يخرج من خزينة بنك');
            }
        }

        // شيك → بيانات إلزامية
        if ($data['payment_method'] === 'cheque') {
            $d = $data['cheque_details'] ?? [];
            if (empty($d['cheque_number']) || empty($d['bank_name']) || empty($d['due_date'])) {
                throw new InvalidArgumentException('بيانات الشيك ناقصة (رقم الشيك، اسم البنك، تاريخ الاستحقاق)');
            }
        }
    }

    private function validateInvoicePayment(PurchaseInvoice $invoice, float $amount): void
    {
        if ($invoice->isFullyPaid()) {
            throw new RuntimeException('فاتورة المشتريات دي متدفعة بالكامل');
        }

        $remaining = $invoice->remaining_amount;
        if ($amount > $remaining + 0.01) {
            throw new RuntimeException(
                sprintf('المبلغ (%.2f) أكبر من المتبقي على الفاتورة (%.2f)', $amount, $remaining)
            );
        }
    }

    // ─── Journal Entry ────────────────────────────────────────────────────────

    /**
     * القيد:
     * - دفع مورد:    مدين 2111/2112 ← دائن الخزينة أو 2120
     * - مصروف تشغيلي: مدين 5xxx    ← دائن الخزينة أو 2120
     */
    private function buildJournalEntry(Payment $payment): JournalEntry
    {
        $debit  = $this->resolveDebitAccount($payment);
        $credit = $this->resolveCreditAccount($payment);

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => $payment->payment_date,
            'description'  => $this->buildEntryDescription($payment),
            'source_type'  => Payment::class,
            'source_id'    => $payment->id,
            'is_manual'    => false,
            'is_posted'    => true,
            'total_debit'  => $payment->amount,
            'total_credit' => $payment->amount,
            'created_by'   => $payment->created_by,
        ]);

        JournalEntryLine::insert([
            [
                'journal_entry_id' => $entry->id,
                'account_id'       => $debit->id,
                'business_unit_id' => $payment->business_unit_id,
                'debit'            => $payment->amount,
                'credit'           => 0,
                'description'      => $payment->isSupplierPayment()
                    ? 'دفع مورد - ' . $payment->payment_number
                    : ($payment->category_label . ' - ' . $payment->payment_number),
                'created_at'       => now(),
            ],
            [
                'journal_entry_id' => $entry->id,
                'account_id'       => $credit->id,
                'business_unit_id' => $payment->business_unit_id,
                'debit'            => 0,
                'credit'           => $payment->amount,
                'description'      => match ($payment->payment_method) {
                    'cash'          => 'صرف كاش - ' . $payment->payment_number,
                    'bank_transfer' => 'تحويل بنكي - ' . $payment->payment_number,
                    'cheque'        => 'شيك صادر #' . ($payment->cheque_details['cheque_number'] ?? '') . ' - ' . $payment->payment_number,
                },
                'created_at'       => now(),
            ],
        ]);

        return $entry;
    }

    private function resolveDebitAccount(Payment $payment): ChartOfAccount
    {
        if ($payment->isSupplierPayment()) {
            $unit = BusinessUnit::findOrFail($payment->business_unit_id);
            $code = match ($unit->type) {
                'showroom'     => '2111',
                'distribution' => '2112',
                default        => '2111',
            };
            return ChartOfAccount::where('code', $code)->firstOrFail();
        }

        // مصروف تشغيلي
        return ChartOfAccount::findOrFail($payment->expense_account_id);
    }

    private function resolveCreditAccount(Payment $payment): ChartOfAccount
    {
        // شيك صادر → 2120
        if ($payment->payment_method === 'cheque') {
            return ChartOfAccount::where('code', '2120')->firstOrFail();
        }

        // كاش / تحويل → الحساب المرتبط بالخزينة
        $treasury = Treasury::with('account')->findOrFail($payment->treasury_id);

        if (! $treasury->account) {
            throw new RuntimeException('الخزينة مش مرتبطة بحساب محاسبي');
        }

        return $treasury->account;
    }

    // ─── Description builders ─────────────────────────────────────────────────

    private function buildTreasuryDescription(Payment $payment): string
    {
        $base = 'سند صرف ' . $payment->payment_number;

        if ($payment->supplier_id) {
            return $base . ' — ' . ($payment->supplier->name ?? '');
        }

        return $base . ' — ' . $payment->category_label;
    }

    private function buildEntryDescription(Payment $payment): string
    {
        if ($payment->isSupplierPayment()) {
            return 'قيد سند صرف ' . $payment->payment_number . ' — دفع لمورد ' . ($payment->supplier->name ?? '');
        }

        return 'قيد سند صرف ' . $payment->payment_number . ' — ' . $payment->category_label;
    }
}
