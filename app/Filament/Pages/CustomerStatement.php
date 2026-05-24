<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Modules\Accounting\LedgerService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class CustomerStatement extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'كشف حساب عميل';

    protected static ?string $navigationLabel = 'كشف حساب عميل';

    protected string $view = 'filament.pages.customer-statement';

    public ?int $customer_id = null;

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
            Select::make('customer_id')
                ->label('العميل')
                ->options(fn () => Customer::orderBy('name')
                    ->get()
                    ->mapWithKeys(fn ($c) => [$c->id => $c->code.' — '.$c->name])
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

    public function getCustomerInfo(): ?Customer
    {
        if (! $this->customer_id) {
            return null;
        }

        return Customer::find($this->customer_id);
    }

    /** كشف الحساب محسوب مرة واحدة لكل طلب (memoized عبر LedgerService). */
    private ?array $statementData = null;

    protected function statement(): array
    {
        if (! $this->customer_id) {
            return ['opening' => 0.0, 'lines' => collect(), 'totalDebit' => 0.0, 'totalCredit' => 0.0, 'closing' => 0.0];
        }

        return $this->statementData ??= app(LedgerService::class)
            ->customerStatement($this->customer_id, $this->from_date, $this->to_date);
    }

    /** رصيد أول المدة */
    public function getOpeningBalance(): float
    {
        return $this->statement()['opening'];
    }

    /** سطور كشف الحساب في الفترة المحددة */
    public function getStatementLines(): Collection
    {
        return $this->statement()['lines'];
    }
}
