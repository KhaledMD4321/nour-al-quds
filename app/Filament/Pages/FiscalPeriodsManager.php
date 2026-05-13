<?php

namespace App\Filament\Pages;

use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class FiscalPeriodsManager extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null  $navigationGroup = 'المحاسبة';
    protected static ?int                   $navigationSort  = 2;
    protected static ?string                $title           = 'إدارة الفترات المالية';
    protected static ?string                $navigationLabel = 'الفترات المالية';
    protected string         $view            = 'filament.pages.fiscal-periods-manager';

    public int $selectedYear;
    public int $newYear;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('accounting.journal.view') ?? false;
    }

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
        $this->newYear      = $this->selectedYear + 1;
    }

    public function getAvailableYears(): array
    {
        $years = FiscalPeriod::selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        return $years;
    }

    public function getPeriods(): \Illuminate\Support\Collection
    {
        return FiscalPeriod::where('year', $this->selectedYear)
            ->orderBy('month')
            ->get()
            ->map(function (FiscalPeriod $period) {

                $journalCount = JournalEntry::whereDate('entry_date', '>=', $period->start_date)
                    ->whereDate('entry_date', '<=', $period->end_date)
                    ->count();

                $invoiceCount = Invoice::where('type', 'sale')
                    ->whereNotIn('status', ['draft', 'cancelled'])
                    ->whereDate('invoice_date', '>=', $period->start_date)
                    ->whereDate('invoice_date', '<=', $period->end_date)
                    ->count();

                $totalSales = Invoice::where('type', 'sale')
                    ->whereNotIn('status', ['draft', 'cancelled'])
                    ->whereDate('invoice_date', '>=', $period->start_date)
                    ->whereDate('invoice_date', '<=', $period->end_date)
                    ->sum('total_amount');

                return (object) [
                    'id'            => $period->id,
                    'year'          => $period->year,
                    'month'         => $period->month,
                    'month_name'    => $this->getArabicMonth($period->month),
                    'start_date'    => $period->start_date,
                    'end_date'      => $period->end_date,
                    'is_locked'     => $period->is_locked,
                    'locked_by'     => $period->locked_by,
                    'locked_at'     => $period->locked_at,
                    'journal_count' => $journalCount,
                    'invoice_count' => $invoiceCount,
                    'total_sales'   => (float) $totalSales,
                    'is_current'    => Carbon::now()->between(
                        Carbon::parse($period->start_date),
                        Carbon::parse($period->end_date)
                    ),
                ];
            });
    }

    public function lockPeriod(int $id): void
    {
        $period = FiscalPeriod::findOrFail($id);

        if ($period->is_locked) {
            Notification::make()->warning()->title('الفترة مقفولة بالفعل')->send();
            return;
        }

        $period->update([
            'is_locked' => true,
            'locked_by' => auth()->id(),
            'locked_at' => now(),
        ]);

        Notification::make()->success()
            ->title("تم قفل فترة {$this->getArabicMonth($period->month)} {$period->year}")
            ->send();
    }

    public function unlockPeriod(int $id): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            Notification::make()->danger()->title('فقط مدير النظام يمكنه فتح فترة مقفولة')->send();
            return;
        }

        $period = FiscalPeriod::findOrFail($id);
        $period->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        Notification::make()->success()
            ->title("تم فتح فترة {$this->getArabicMonth($period->month)} {$period->year}")
            ->send();
    }

    public function generateYear(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            Notification::make()->danger()->title('فقط مدير النظام يمكنه إنشاء فترات جديدة')->send();
            return;
        }

        $existing = FiscalPeriod::where('year', $this->newYear)->count();
        if ($existing > 0) {
            Notification::make()->warning()
                ->title("فترات سنة {$this->newYear} موجودة بالفعل ({$existing} فترة)")
                ->send();
            return;
        }

        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($this->newYear, $m, 1);
            FiscalPeriod::create([
                'year'       => $this->newYear,
                'month'      => $m,
                'start_date' => $start->toDateString(),
                'end_date'   => $start->copy()->endOfMonth()->toDateString(),
                'is_locked'  => false,
            ]);
        }

        $this->selectedYear = $this->newYear;
        Notification::make()->success()
            ->title("تم إنشاء 12 فترة لسنة {$this->newYear}")
            ->send();
    }

    public function lockAllBefore(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            return;
        }

        $currentMonth = (int) date('m');
        $currentYear  = (int) date('Y');

        $count = FiscalPeriod::where(function ($q) use ($currentYear, $currentMonth) {
            $q->where('year', '<', $currentYear)
              ->orWhere(function ($q2) use ($currentYear, $currentMonth) {
                  $q2->where('year', $currentYear)->where('month', '<', $currentMonth);
              });
        })
        ->where('is_locked', false)
        ->update([
            'is_locked' => true,
            'locked_by' => auth()->id(),
            'locked_at' => now(),
        ]);

        Notification::make()->success()->title("تم قفل {$count} فترة سابقة")->send();
    }

    protected function getArabicMonth(int $month): string
    {
        return match ($month) {
            1  => 'يناير',  2  => 'فبراير', 3  => 'مارس',
            4  => 'أبريل',  5  => 'مايو',   6  => 'يونيو',
            7  => 'يوليو',  8  => 'أغسطس',  9  => 'سبتمبر',
            10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
            default => (string) $month,
        };
    }
}
