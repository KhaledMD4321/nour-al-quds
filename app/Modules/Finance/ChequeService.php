<?php

namespace App\Modules\Finance;

use App\Models\BusinessUnit;
use App\Models\ChartOfAccount;
use App\Models\Cheque;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Treasury;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ChequeService
{
    public function __construct(
        protected TreasuryService $treasuryService,
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    //  1. تسجيل شيك جديد
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * التسجيل الأوتوماتيكي يحصل من ReceiptService (واردة) أو PaymentService (صادرة).
     * هذا الـ method لتسجيل مستقل من الواجهة.
     */
    public function register(array $data, int $createdBy): Cheque
    {
        $this->validateRegistration($data);

        if (empty($data['business_unit_id']) || (int) $data['business_unit_id'] === 0) {
            $data['business_unit_id'] = auth()->user()?->business_unit_id;
        }

        return DB::transaction(function () use ($data, $createdBy) {
            $cheque = Cheque::create([
                'cheque_number' => $data['cheque_number'],
                'bank_name' => $data['bank_name'],
                'amount' => $data['amount'],
                'issue_date' => $data['issue_date'] ?? today()->toDateString(),
                'due_date' => $data['due_date'],
                'direction' => $data['direction'],
                'status' => 'pending',
                'treasury_id' => $data['treasury_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'business_unit_id' => $data['business_unit_id'],
                'receipt_id' => $data['receipt_id'] ?? null,
                'payment_id' => $data['payment_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            // لو تسجيل مستقل (مش من receipt/payment)، نولّد قيد التسجيل
            if (empty($data['receipt_id']) && empty($data['payment_id'])) {
                $entry = $this->generateRegistrationEntry($cheque);
                $cheque->update(['journal_entry_id' => $entry->id]);
            }

            return $cheque->fresh();
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  2. إيداع الشيك بالبنك (incoming + pending فقط)
    // ══════════════════════════════════════════════════════════════════════════

    public function deposit(int $chequeId, int $bankTreasuryId, int $userId): Cheque
    {
        return DB::transaction(function () use ($chequeId, $bankTreasuryId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($chequeId);

            if (! $cheque->canDeposit()) {
                throw new RuntimeException(
                    "مش ممكن إيداع الشيك ده — الحالة الحالية: {$cheque->status_label}"
                );
            }

            $treasury = Treasury::findOrFail($bankTreasuryId);
            if ($treasury->type !== 'bank') {
                throw new InvalidArgumentException('الإيداع لازم يكون في خزينة بنك');
            }

            $cheque->update([
                'status' => 'deposited',
                'treasury_id' => $bankTreasuryId,
                'deposited_at' => now(),
            ]);

            // لا قيد عند الإيداع — القيد بيحصل عند التحصيل الفعلي

            return $cheque->fresh();
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  3. تحصيل الشيك (collected)
    // ══════════════════════════════════════════════════════════════════════════

    public function collect(int $chequeId, int $userId, ?int $bankTreasuryId = null): Cheque
    {
        return DB::transaction(function () use ($chequeId, $userId, $bankTreasuryId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($chequeId);

            if (! $cheque->canCollect()) {
                throw new RuntimeException(
                    "مش ممكن تحصيل الشيك ده — الحالة الحالية: {$cheque->status_label}"
                );
            }

            // لو صادر وتم تمرير treasury_id جديد، حدّثه
            if ($cheque->isOutgoing() && $bankTreasuryId) {
                $treasury = Treasury::findOrFail($bankTreasuryId);
                if ($treasury->type !== 'bank') {
                    throw new InvalidArgumentException('الصرف لازم يكون من خزينة بنك');
                }
                $cheque->update(['treasury_id' => $bankTreasuryId]);
                $cheque->refresh();
            }

            if (! $cheque->treasury_id) {
                throw new RuntimeException(
                    $cheque->isIncoming()
                        ? 'الشيك لازم يكون مودع في بنك أولاً قبل التحصيل'
                        : 'لازم تحدد البنك اللي هيتخصم منه الشيك الصادر'
                );
            }

            if ($cheque->isIncoming()) {
                // شيك وارد: تحصيل → إضافة للبنك
                $this->treasuryService->addFunds(
                    treasuryId: $cheque->treasury_id,
                    amount: (float) $cheque->amount,
                    description: "تحصيل شيك #{$cheque->cheque_number} - ".($cheque->customer->name ?? ''),
                    referenceType: Cheque::class,
                    referenceId: $cheque->id,
                    createdBy: $userId,
                    type: 'receipt',
                );

                // القيد: مدين البنك / دائن 1130
                $entry = $this->generateCollectionEntry($cheque);

            } else {
                // شيك صادر: تحصيل → خصم من البنك
                $this->treasuryService->deductFunds(
                    treasuryId: $cheque->treasury_id,
                    amount: (float) $cheque->amount,
                    description: "صرف شيك #{$cheque->cheque_number} - ".($cheque->supplier->name ?? ''),
                    referenceType: Cheque::class,
                    referenceId: $cheque->id,
                    createdBy: $userId,
                    type: 'payment',
                );

                // القيد: مدين 2120 / دائن البنك
                $entry = $this->generateOutgoingCollectionEntry($cheque);
            }

            $cheque->update([
                'status' => 'collected',
                'collected_at' => now(),
                'journal_entry_id' => $entry->id,
            ]);

            return $cheque->fresh();
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  4. رفض الشيك (bounced — incoming + deposited فقط)
    // ══════════════════════════════════════════════════════════════════════════

    public function bounce(int $chequeId, string $reason, int $userId): Cheque
    {
        return DB::transaction(function () use ($chequeId, $reason) {
            $cheque = Cheque::lockForUpdate()->findOrFail($chequeId);

            if (! $cheque->canBounce()) {
                throw new RuntimeException(
                    "مش ممكن رفض الشيك ده — الحالة الحالية: {$cheque->status_label}"
                );
            }

            // القيد: مدين عملاء / دائن 1130 (الدين يرجع للعميل)
            $entry = $this->generateBounceEntry($cheque);

            $cheque->update([
                'status' => 'bounced',
                'bounced_at' => now(),
                'bounce_reason' => $reason,
                'journal_entry_id' => $entry->id,
            ]);

            return $cheque->fresh();
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  5. استبدال شيك مرفوض بشيك جديد (incoming + bounced فقط)
    // ══════════════════════════════════════════════════════════════════════════

    public function replace(int $oldChequeId, array $newData, int $userId): Cheque
    {
        return DB::transaction(function () use ($oldChequeId, $newData, $userId) {
            $old = Cheque::lockForUpdate()->findOrFail($oldChequeId);

            if (! $old->canReplace()) {
                throw new RuntimeException('الاستبدال متاح فقط للشيكات الواردة المرفوضة');
            }

            // تسجيل الشيك الجديد بنفس بيانات العميل والوحدة
            $newData['direction'] = 'incoming';
            $newData['customer_id'] = $old->customer_id;
            $newData['business_unit_id'] = $old->business_unit_id;

            // منع القيد التلقائي — سنولّده يدوياً
            $new = $this->register($newData, $userId);

            // ربط القديم بالجديد وتحديث حالته
            $old->update([
                'status' => 'replaced',
                'replaced_by_id' => $new->id,
            ]);

            return $new;
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  6. استعلامات مساعدة
    // ══════════════════════════════════════════════════════════════════════════

    public function getUpcomingDue(int $days = 7, ?int $unitId = null)
    {
        return Cheque::incoming()
            ->pending()
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->whereBetween('due_date', [today(), today()->addDays($days)])
            ->with(['customer', 'businessUnit'])
            ->orderBy('due_date')
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  القيود المحاسبية (Private)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * قيد تسجيل مستقل:
     *   وارد  → مدين 1130 / دائن 1121 أو 1122
     *   صادر  → مدين 2111 أو 2112 / دائن 2120
     */
    protected function generateRegistrationEntry(Cheque $cheque): JournalEntry
    {
        if ($cheque->isIncoming()) {
            $debit = ChartOfAccount::where('code', '1130')->firstOrFail();
            $credit = $this->resolveCustomerAccount($cheque);
        } else {
            $debit = $this->resolveSupplierAccount($cheque);
            $credit = ChartOfAccount::where('code', '2120')->firstOrFail();
        }

        return $this->buildEntry(
            cheque: $cheque,
            debit: $debit,
            credit: $credit,
            desc: "قيد تسجيل شيك #{$cheque->cheque_number}",
        );
    }

    /**
     * قيد تحصيل شيك وارد:
     *   مدين البنك / دائن 1130
     */
    protected function generateCollectionEntry(Cheque $cheque): JournalEntry
    {
        $treasury = Treasury::with('account')->findOrFail($cheque->treasury_id);

        if (! $treasury->account) {
            throw new RuntimeException('الخزينة مش مرتبطة بحساب محاسبي');
        }

        $debit = $treasury->account;
        $credit = ChartOfAccount::where('code', '1130')->firstOrFail();

        return $this->buildEntry(
            cheque: $cheque,
            debit: $debit,
            credit: $credit,
            desc: "تحصيل شيك #{$cheque->cheque_number} — ".($cheque->customer->name ?? ''),
        );
    }

    /**
     * قيد صرف شيك صادر:
     *   مدين 2120 / دائن البنك
     */
    protected function generateOutgoingCollectionEntry(Cheque $cheque): JournalEntry
    {
        $debit = ChartOfAccount::where('code', '2120')->firstOrFail();
        $treasury = Treasury::with('account')->findOrFail($cheque->treasury_id);

        if (! $treasury->account) {
            throw new RuntimeException('الخزينة مش مرتبطة بحساب محاسبي');
        }

        $credit = $treasury->account;

        return $this->buildEntry(
            cheque: $cheque,
            debit: $debit,
            credit: $credit,
            desc: "صرف شيك #{$cheque->cheque_number} — ".($cheque->supplier->name ?? ''),
        );
    }

    /**
     * قيد رفض الشيك:
     *   مدين 1121/1122 (عملاء) / دائن 1130 — الدين يرجع للعميل
     */
    protected function generateBounceEntry(Cheque $cheque): JournalEntry
    {
        $debit = $this->resolveCustomerAccount($cheque);
        $credit = ChartOfAccount::where('code', '1130')->firstOrFail();

        return $this->buildEntry(
            cheque: $cheque,
            debit: $debit,
            credit: $credit,
            desc: "رفض شيك #{$cheque->cheque_number} — ".($cheque->customer->name ?? ''),
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function buildEntry(
        Cheque $cheque,
        ChartOfAccount $debit,
        ChartOfAccount $credit,
        string $desc
    ): JournalEntry {
        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date' => today()->toDateString(),
            'description' => $desc,
            'source_type' => Cheque::class,
            'source_id' => $cheque->id,
            'is_manual' => false,
            'is_posted' => true,
            'total_debit' => $cheque->amount,
            'total_credit' => $cheque->amount,
            'created_by' => auth()->id() ?? $cheque->created_by,
        ]);

        JournalEntryLine::insert([
            [
                'journal_entry_id' => $entry->id,
                'account_id' => $debit->id,
                'business_unit_id' => $cheque->business_unit_id,
                'debit' => $cheque->amount,
                'credit' => 0,
                'description' => $desc.' (مدين)',
                'created_at' => now(),
            ],
            [
                'journal_entry_id' => $entry->id,
                'account_id' => $credit->id,
                'business_unit_id' => $cheque->business_unit_id,
                'debit' => 0,
                'credit' => $cheque->amount,
                'description' => $desc.' (دائن)',
                'created_at' => now(),
            ],
        ]);

        return $entry;
    }

    protected function resolveCustomerAccount(Cheque $cheque): ChartOfAccount
    {
        $unit = BusinessUnit::findOrFail($cheque->business_unit_id);
        $code = $unit->type === 'showroom' ? '1121' : '1122';

        return ChartOfAccount::where('code', $code)->firstOrFail();
    }

    protected function resolveSupplierAccount(Cheque $cheque): ChartOfAccount
    {
        $unit = BusinessUnit::findOrFail($cheque->business_unit_id);
        $code = $unit->type === 'showroom' ? '2111' : '2112';

        return ChartOfAccount::where('code', $code)->firstOrFail();
    }

    protected function validateRegistration(array $data): void
    {
        if (empty($data['cheque_number'])) {
            throw new InvalidArgumentException('رقم الشيك مطلوب');
        }
        if (empty($data['bank_name'])) {
            throw new InvalidArgumentException('اسم البنك مطلوب');
        }
        if (empty($data['amount']) || (float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('المبلغ لازم يكون أكبر من صفر');
        }
        if (empty($data['due_date'])) {
            throw new InvalidArgumentException('تاريخ الاستحقاق مطلوب');
        }
        if (! in_array($data['direction'] ?? '', ['incoming', 'outgoing'])) {
            throw new InvalidArgumentException('الاتجاه غير صحيح (incoming أو outgoing)');
        }
        if ($data['direction'] === 'incoming' && empty($data['customer_id'])) {
            throw new InvalidArgumentException('لازم تحدد العميل للشيك الوارد');
        }
        if ($data['direction'] === 'outgoing' && empty($data['supplier_id'])) {
            throw new InvalidArgumentException('لازم تحدد المورد للشيك الصادر');
        }
    }
}
