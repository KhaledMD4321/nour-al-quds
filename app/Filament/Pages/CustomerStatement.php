<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Receipt;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class CustomerStatement extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null   $navigationGroup = 'التقارير';
    protected static ?int                    $navigationSort  = 15;
    protected static ?string                 $title           = 'كشف حساب عميل';
    protected static ?string                 $navigationLabel = 'كشف حساب عميل';
    protected string                         $view            = 'filament.pages.customer-statement';

    public ?int    $customer_id = null;
    public ?string $from_date   = null;
    public ?string $to_date     = null;

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
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')
                ->label('العميل')
                ->options(fn () => Customer::orderBy('name')
                    ->get()
                    ->mapWithKeys(fn ($c) => [$c->id => $c->code . ' — ' . $c->name])
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
        if (! $this->customer_id) return null;
        return Customer::find($this->customer_id);
    }

    /**
     * رصيد أول المدة — كل الحركات قبل from_date
     */
    public function getOpeningBalance(): float
    {
        if (! $this->customer_id) return 0;

        $customer = Customer::find($this->customer_id);
        if (! $customer) return 0;

        $opening = (float) ($customer->opening_balance ?? 0);

        if ($this->from_date) {
            $invBefore = (float) Invoice::where('customer_id', $this->customer_id)
                ->where('type', 'sale')
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereDate('invoice_date', '<', $this->from_date)
                ->sum('total_amount');

            $retBefore = (float) Invoice::where('customer_id', $this->customer_id)
                ->where('type', 'sale_return')
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereDate('invoice_date', '<', $this->from_date)
                ->sum('total_amount');

            $recBefore = (float) Receipt::where('customer_id', $this->customer_id)
                ->whereDate('receipt_date', '<', $this->from_date)
                ->sum('amount');

            $opening = $opening + $invBefore - $retBefore - $recBefore;
        }

        return $opening;
    }

    /**
     * سطور كشف الحساب في الفترة المحددة
     */
    public function getStatementLines(): Collection
    {
        if (! $this->customer_id) return collect();

        $lines = collect();

        // 1. فواتير المبيعات — مدين على العميل
        Invoice::where('customer_id', $this->customer_id)
            ->where('type', 'sale')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->when($this->from_date, fn ($q) => $q->whereDate('invoice_date', '>=', $this->from_date))
            ->when($this->to_date,   fn ($q) => $q->whereDate('invoice_date', '<=', $this->to_date))
            ->orderBy('invoice_date')
            ->each(function ($inv) use ($lines) {
                $lines->push((object) [
                    'date'        => $inv->invoice_date,
                    'reference'   => $inv->reference_number,
                    'description' => 'فاتورة مبيعات',
                    'debit'       => (float) $inv->total_amount,
                    'credit'      => 0.0,
                ]);
            });

        // 2. مرتجعات المبيعات — دائن
        Invoice::where('customer_id', $this->customer_id)
            ->where('type', 'sale_return')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->when($this->from_date, fn ($q) => $q->whereDate('invoice_date', '>=', $this->from_date))
            ->when($this->to_date,   fn ($q) => $q->whereDate('invoice_date', '<=', $this->to_date))
            ->orderBy('invoice_date')
            ->each(function ($ret) use ($lines) {
                $lines->push((object) [
                    'date'        => $ret->invoice_date,
                    'reference'   => $ret->reference_number,
                    'description' => 'مرتجع مبيعات',
                    'debit'       => 0.0,
                    'credit'      => (float) $ret->total_amount,
                ]);
            });

        // 3. سندات القبض — دائن (العميل دفع)
        Receipt::where('customer_id', $this->customer_id)
            ->when($this->from_date, fn ($q) => $q->whereDate('receipt_date', '>=', $this->from_date))
            ->when($this->to_date,   fn ($q) => $q->whereDate('receipt_date', '<=', $this->to_date))
            ->orderBy('receipt_date')
            ->each(function ($rec) use ($lines) {
                $method = match ($rec->payment_method) {
                    'cash'          => 'كاش',
                    'cheque'        => 'شيك',
                    'bank_transfer' => 'تحويل بنكي',
                    default         => $rec->payment_method,
                };
                $lines->push((object) [
                    'date'        => $rec->receipt_date,
                    'reference'   => $rec->receipt_number,
                    'description' => "سند قبض ({$method})",
                    'debit'       => 0.0,
                    'credit'      => (float) $rec->amount,
                ]);
            });

        return $lines->sortBy('date')->values();
    }
}
