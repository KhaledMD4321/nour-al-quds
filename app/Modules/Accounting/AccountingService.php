<?php

namespace App\Modules\Accounting;

use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AccountingService
{
    // ══════════════════════════════════════════════════════════════════════════
    //  إنشاء قيد يدوي
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * إنشاء قيد يدوي جديد.
     * لو $data['lines'] موجودة يتحقق من التوازن مباشرة.
     * لو مفيش lines — القيد يُحفظ ويُضاف السطور بعدين عبر RelationManager.
     */
    public function createManualEntry(array $data, int $createdBy): JournalEntry
    {
        $date = Carbon::parse($data['entry_date'] ?? today());
        $this->checkFiscalPeriod($date);

        return DB::transaction(function () use ($data, $createdBy, $date) {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date' => $date->toDateString(),
                'description' => $data['description'],
                'source_type' => null,
                'source_id' => null,
                'is_manual' => true,
                'is_posted' => true,
                'total_debit' => 0,
                'total_credit' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            // لو السطور جاية مع الـ data (من Tinker أو API)
            if (! empty($data['lines'])) {
                $totalDebit = 0.0;
                $totalCredit = 0.0;

                foreach ($data['lines'] as $line) {
                    $debit = (float) ($line['debit'] ?? 0);
                    $credit = (float) ($line['credit'] ?? 0);

                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $line['account_id'],
                        'business_unit_id' => $line['business_unit_id']
                            ?? $data['business_unit_id']
                            ?? auth()->user()?->business_unit_id,
                        'debit' => $debit,
                        'credit' => $credit,
                        'description' => $line['description'] ?? $data['description'],
                    ]);

                    $totalDebit += $debit;
                    $totalCredit += $credit;
                }

                // فحص التوازن
                if (abs($totalDebit - $totalCredit) > 0.01) {
                    throw new InvalidArgumentException(
                        'القيد غير متوازن! مدين: '.number_format($totalDebit, 2)
                        .' ≠ دائن: '.number_format($totalCredit, 2)
                    );
                }

                $entry->update([
                    'total_debit' => round($totalDebit, 2),
                    'total_credit' => round($totalCredit, 2),
                ]);
            }

            return $entry->fresh();
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  عكس قيد (Reverse Entry)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * يُنشئ قيد جديد بنفس المبالغ مع عكس المدين والدائن.
     * يُستخدم لتصحيح الأخطاء المحاسبية.
     */
    public function reverseEntry(int $entryId, int $createdBy): JournalEntry
    {
        $original = JournalEntry::with('lines')->findOrFail($entryId);

        $this->checkFiscalPeriod(today());

        if ($original->lines->isEmpty()) {
            throw new RuntimeException('القيد لا يحتوي على سطور — لا يمكن عكسه');
        }

        return DB::transaction(function () use ($original, $createdBy) {
            $reverse = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date' => today()->toDateString(),
                'description' => 'عكس قيد #'.$original->entry_number.': '.$original->description,
                'source_type' => $original->source_type,
                'source_id' => $original->source_id,
                'is_manual' => true,
                'is_posted' => true,
                'total_debit' => $original->total_credit, // مُعكَّس
                'total_credit' => $original->total_debit,  // مُعكَّس
                'created_by' => $createdBy,
            ]);

            foreach ($original->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reverse->id,
                    'account_id' => $line->account_id,
                    'business_unit_id' => $line->business_unit_id,
                    'debit' => $line->credit, // مُعكَّس
                    'credit' => $line->debit,  // مُعكَّس
                    'description' => 'عكس: '.($line->description ?? ''),
                ]);
            }

            return $reverse->fresh();
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  تحديث إجمالي المدين والدائن (بعد إضافة/حذف سطور من RelationManager)
    // ══════════════════════════════════════════════════════════════════════════

    public function recalculateTotals(int $entryId): void
    {
        $entry = JournalEntry::findOrFail($entryId);
        $entry->update([
            'total_debit' => round((float) $entry->lines()->sum('debit'), 2),
            'total_credit' => round((float) $entry->lines()->sum('credit'), 2),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  فحص الفترة المالية
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * يرفض بـ RuntimeException لو الفترة مقفولة.
     * لو مفيش فترة مسجلة للتاريخ ده — يمرر بدون رفض (مرونة بالنسبة للبيانات القديمة).
     */
    public function checkFiscalPeriod(Carbon|\DateTimeInterface|string $date): void
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        $period = FiscalPeriod::forDate($carbon)->first();

        if ($period && $period->is_locked) {
            throw new RuntimeException(
                'الفترة المالية مقفولة ('
                .$period->getDisplayName()
                .'). تواصل مع المحاسب لفتحها.'
            );
        }
    }
}
