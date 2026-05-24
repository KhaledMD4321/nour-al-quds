<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class TrialBalance extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|\UnitEnum|null $navigationGroup = 'المحاسبة';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'ميزان المراجعة';

    protected static ?string $navigationLabel = 'ميزان المراجعة';

    protected string $view = 'filament.pages.trial-balance';

    public ?string $from_date = null;

    public ?string $to_date = null;

    public ?int $business_unit_id = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('accounting.trial_balance.view');
    }

    public function mount(): void
    {
        $this->from_date = today()->startOfYear()->toDateString();
        $this->to_date = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('from_date')
                ->label('من تاريخ')
                ->live()
                ->displayFormat('Y-m-d'),

            DatePicker::make('to_date')
                ->label('إلى تاريخ')
                ->live()
                ->displayFormat('Y-m-d'),

            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(3);
    }

    public function getBalances()
    {
        return ChartOfAccount::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                $query = JournalEntryLine::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($q) {
                        $q->when($this->from_date, fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
                            ->when($this->to_date, fn ($q, $d) => $q->whereDate('entry_date', '<=', $d));
                    })
                    ->when($this->business_unit_id, fn ($q) => $q->where('business_unit_id', $this->business_unit_id));

                $debit = (float) $query->sum('debit');
                $credit = (float) $query->sum('credit');
                $net = $debit - $credit;

                if ($debit == 0 && $credit == 0) {
                    return null;
                }

                return (object) [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'total_debit' => $debit,
                    'total_credit' => $credit,
                    'balance_debit' => $net > 0 ? $net : 0,
                    'balance_credit' => $net < 0 ? abs($net) : 0,
                ];
            })
            ->filter(); // إزالة الحسابات بدون حركات
    }
}
