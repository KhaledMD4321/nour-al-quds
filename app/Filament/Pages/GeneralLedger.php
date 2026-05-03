<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class GeneralLedger extends Page
{

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-book-open';
    protected static string|\UnitEnum|null   $navigationGroup = 'المحاسبة';
    protected static ?int                    $navigationSort  = 10;
    protected static ?string                 $title           = 'دفتر الأستاذ';
    protected static ?string                 $navigationLabel = 'دفتر الأستاذ';
    protected string         $view            = 'filament.pages.general-ledger';

    public ?int    $account_id      = null;
    public ?string $from_date       = null;
    public ?string $to_date         = null;
    public ?int    $business_unit_id = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('accounting.ledger.view');
    }

    public function mount(): void
    {
        $this->from_date = today()->startOfMonth()->toDateString();
        $this->to_date   = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('account_id')
                ->label('الحساب')
                ->options(fn () => ChartOfAccount::where('is_active', true)
                    ->orderBy('code')
                    ->get()
                    ->mapWithKeys(fn ($a) => [$a->id => $a->code . ' — ' . $a->name])
                    ->toArray()
                )
                ->searchable()
                ->live(),

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
        ])->columns(4);
    }

    public function getLines()
    {
        if (! $this->account_id) return collect();

        return JournalEntryLine::with(['journalEntry', 'account'])
            ->where('account_id', $this->account_id)
            ->whereHas('journalEntry', function ($q) {
                $q->when($this->from_date, fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
                  ->when($this->to_date,   fn ($q, $d) => $q->whereDate('entry_date', '<=', $d));
            })
            ->when($this->business_unit_id, fn ($q) => $q->where('business_unit_id', $this->business_unit_id))
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*')
            ->get();
    }

    public function getAccountName(): string
    {
        if (! $this->account_id) return '';
        $a = ChartOfAccount::find($this->account_id);
        return $a ? "{$a->code} — {$a->name}" : '';
    }

    public function getTotals(): array
    {
        $lines = $this->getLines();
        $debit  = (float) $lines->sum('debit');
        $credit = (float) $lines->sum('credit');
        return [
            'debit'   => $debit,
            'credit'  => $credit,
            'balance' => $debit - $credit,
        ];
    }
}
