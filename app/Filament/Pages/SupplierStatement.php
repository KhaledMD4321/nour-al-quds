<?php

namespace App\Filament\Pages;

use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class SupplierStatement extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null   $navigationGroup = 'التقارير';
    protected static ?int                    $navigationSort  = 16;
    protected static ?string                 $title           = 'كشف حساب مورد';
    protected static ?string                 $navigationLabel = 'كشف حساب مورد';
    protected string                         $view            = 'filament.pages.supplier-statement';

    public ?int    $supplier_id = null;
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
            Select::make('supplier_id')
                ->label('المورد')
                ->options(fn () => Supplier::orderBy('name')
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->id => $s->code . ' — ' . $s->name])
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
        if (! $this->supplier_id) return null;
        return Supplier::find($this->supplier_id);
    }

    /**
     * رصيد أول المدة (دائن على المورد = مبالغ مستحقة له)
     */
    public function getOpeningBalance(): float
    {
        if (! $this->supplier_id) return 0;

        $supplier = Supplier::find($this->supplier_id);
        if (! $supplier) return 0;

        $opening = (float) ($supplier->opening_balance ?? 0);

        if ($this->from_date) {
            // فواتير الشراء قبل الفترة — تزيد رصيد المورد (دائن)
            $invBefore = (float) PurchaseInvoice::where('supplier_id', $this->supplier_id)
                ->whereIn('status', ['confirmed', 'paid'])
                ->whereDate('invoice_date', '<', $this->from_date)
                ->sum('total_amount');

            // مرتجعات الشراء — تنقص رصيد المورد (مدين)
            $retBefore = (float) PurchaseReturn::where('supplier_id', $this->supplier_id)
                ->whereDate('return_date', '<', $this->from_date)
                ->sum('total_amount');

            // مدفوعات للمورد — تنقص رصيده (مدين)
            $payBefore = (float) Payment::where('supplier_id', $this->supplier_id)
                ->whereDate('payment_date', '<', $this->from_date)
                ->sum('amount');

            $opening = $opening + $invBefore - $retBefore - $payBefore;
        }

        return $opening;
    }

    /**
     * سطور كشف حساب المورد
     * المنطق معكوس عن العميل:
     *   فاتورة شراء   = دائن (المورد له حق علينا)
     *   مرتجع مشتريات = مدين (ينقص ما له علينا)
     *   مدفوعات       = مدين (نقدنا له)
     */
    public function getStatementLines(): Collection
    {
        if (! $this->supplier_id) return collect();

        $lines = collect();

        // 1. فواتير الشراء — دائن على المورد
        PurchaseInvoice::where('supplier_id', $this->supplier_id)
            ->whereIn('status', ['confirmed', 'paid'])
            ->when($this->from_date, fn ($q) => $q->whereDate('invoice_date', '>=', $this->from_date))
            ->when($this->to_date,   fn ($q) => $q->whereDate('invoice_date', '<=', $this->to_date))
            ->orderBy('invoice_date')
            ->each(function ($inv) use ($lines) {
                $lines->push((object) [
                    'date'        => $inv->invoice_date,
                    'reference'   => $inv->invoice_number ?? $inv->reference_number,
                    'description' => 'فاتورة مشتريات',
                    'debit'       => 0.0,
                    'credit'      => (float) $inv->total_amount,
                ]);
            });

        // 2. مرتجعات المشتريات — مدين (تنقص ما للمورد)
        PurchaseReturn::where('supplier_id', $this->supplier_id)
            ->when($this->from_date, fn ($q) => $q->whereDate('return_date', '>=', $this->from_date))
            ->when($this->to_date,   fn ($q) => $q->whereDate('return_date', '<=', $this->to_date))
            ->orderBy('return_date')
            ->each(function ($ret) use ($lines) {
                $lines->push((object) [
                    'date'        => $ret->return_date,
                    'reference'   => $ret->reference_number,
                    'description' => 'مرتجع مشتريات',
                    'debit'       => (float) $ret->total_amount,
                    'credit'      => 0.0,
                ]);
            });

        // 3. المدفوعات للمورد — مدين (قلّصنا ما عليه)
        Payment::where('supplier_id', $this->supplier_id)
            ->where('category', 'supplier_payment')
            ->when($this->from_date, fn ($q) => $q->whereDate('payment_date', '>=', $this->from_date))
            ->when($this->to_date,   fn ($q) => $q->whereDate('payment_date', '<=', $this->to_date))
            ->orderBy('payment_date')
            ->each(function ($pay) use ($lines) {
                $method = match ($pay->payment_method) {
                    'cash'          => 'كاش',
                    'cheque'        => 'شيك',
                    'bank_transfer' => 'تحويل بنكي',
                    default         => $pay->payment_method,
                };
                $lines->push((object) [
                    'date'        => $pay->payment_date,
                    'reference'   => $pay->payment_number,
                    'description' => "سند صرف ({$method})",
                    'debit'       => (float) $pay->amount,
                    'credit'      => 0.0,
                ]);
            });

        return $lines->sortBy('date')->values();
    }
}
