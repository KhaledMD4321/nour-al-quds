<?php

namespace App\Filament\Pages;

use App\Models\Supplier;
use App\Modules\Accounting\LedgerService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class SupplierStatement extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'كشف حساب مورد';

    protected static ?string $navigationLabel = 'كشف حساب مورد';

    protected string $view = 'filament.pages.supplier-statement';

    public ?int $supplier_id = null;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('accounting.ledger.view');
    }

    public function mount(): void
    {
        $this->from_date = today()->startOfMonth()->toDateString();
        $this->to_date = today()->toDateString();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('supplier_id')
                ->label('المورد')
                ->options(fn () => Supplier::orderBy('name')
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->id => $s->code.' — '.$s->name])
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
        ])->columns(3);
    }

    public function getSupplierInfo(): ?Supplier
    {
        if (! $this->supplier_id) {
            return null;
        }

        return Supplier::find($this->supplier_id);
    }

    /** كشف الحساب محسوب مرة واحدة لكل طلب (memoized عبر LedgerService). */
    private ?array $statementData = null;

    protected function statement(): array
    {
        if (! $this->supplier_id) {
            return ['opening' => 0.0, 'lines' => collect(), 'totalDebit' => 0.0, 'totalCredit' => 0.0, 'closing' => 0.0];
        }

        return $this->statementData ??= app(LedgerService::class)
            ->supplierStatement($this->supplier_id, $this->from_date, $this->to_date);
    }

    /** رصيد أول المدة (دائن على المورد = مبالغ مستحقة له) */
    public function getOpeningBalance(): float
    {
        return $this->statement()['opening'];
    }

    /** سطور كشف حساب المورد */
    public function getStatementLines(): Collection
    {
        return $this->statement()['lines'];
    }
}
