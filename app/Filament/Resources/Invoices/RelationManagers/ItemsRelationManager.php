<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Models\Invoice;
use App\Models\PriceListVersion;
use App\Models\Product;
use App\Models\Stock;
use App\Modules\Sales\InvoiceService;
use App\Modules\Sales\PriceCalculator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'بنود الفاتورة';

    // ── للقراءة فقط لو الفاتورة مش مسودة ────────────────────────────────────────
    public function isReadOnly(): bool
    {
        /** @var Invoice $invoice */
        $invoice = $this->getOwnerRecord();

        return ! $invoice->isDraft();
    }

    // ── Form ─────────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── اختيار المنتج — يملأ السعر والخصومات تلقائياً ─────────────────
            Select::make('product_id')
                ->label('المنتج')
                ->options(Product::where('is_active', true)->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (! $state) {
                        return;
                    }

                    /** @var Invoice $invoice */
                    $invoice = $this->getOwnerRecord();

                    // 1. سعر اللستة من قائمة الأسعار النشطة
                    $product = Product::find($state);
                    $listPrice = $product ? ($product->getCurrentPrice() ?? 0) : 0;
                    $versionId = null;

                    if ($product?->company_id) {
                        $version = PriceListVersion::where('company_id', $product->company_id)
                            ->where('status', 'active')
                            ->latest('effective_date')
                            ->first();
                        $versionId = $version?->id;
                        if ($version && $listPrice == 0) {
                            $listPrice = (float) ($version->getPriceFor($state) ?? 0);
                        }
                    }

                    // 2. خصومات العميل الافتراضية
                    $customer = $invoice->customer;
                    $d1 = $customer ? (float) $customer->default_discount_1 : 0;
                    $d2 = $customer ? (float) $customer->default_discount_2 : 0;
                    $d3 = $customer ? (float) $customer->default_discount_3 : 0;

                    // 3. الكمية المتاحة
                    $available = Stock::where('warehouse_id', $invoice->warehouse_id)
                        ->where('product_id', $state)
                        ->value('quantity') ?? 0;

                    $unitPrice = PriceCalculator::calculateUnitPrice($listPrice, $d1, $d2, $d3);

                    $set('list_price', $listPrice);
                    $set('discount_1', $d1);
                    $set('discount_2', $d2);
                    $set('discount_3', $d3);
                    $set('unit_price', $unitPrice);
                    $set('total', $unitPrice); // quantity=1 by default
                    $set('price_list_version_id', $versionId);
                    $set('_available', (float) $available);
                }),

            TextInput::make('_available')
                ->label('الكمية المتاحة')
                ->disabled()
                ->dehydrated(false)
                ->numeric()
                ->suffix('وحدة'),

            TextInput::make('quantity')
                ->label('الكمية')
                ->required()
                ->numeric()
                ->minValue(0.001)
                ->default(1)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $qty = (float) ($state ?? 1);
                    $unitPrice = (float) ($get('unit_price') ?? 0);
                    $set('total', round($qty * $unitPrice, 2));
                }),

            TextInput::make('list_price')
                ->label('سعر اللستة')
                ->numeric()
                ->prefix('ج.م.')
                ->default(0)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    self::recalcUnitPrice($state, $get, $set);
                }),

            TextInput::make('discount_1')
                ->label('خصم 1 (%)')
                ->numeric()
                ->suffix('%')
                ->default(0)
                ->minValue(0)->maxValue(100)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    self::recalcUnitPrice($get('list_price'), $get, $set);
                }),

            TextInput::make('discount_2')
                ->label('خصم 2 (%)')
                ->numeric()
                ->suffix('%')
                ->default(0)
                ->minValue(0)->maxValue(100)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    self::recalcUnitPrice($get('list_price'), $get, $set);
                }),

            TextInput::make('discount_3')
                ->label('خصم 3 (%)')
                ->numeric()
                ->suffix('%')
                ->default(0)
                ->minValue(0)->maxValue(100)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    self::recalcUnitPrice($get('list_price'), $get, $set);
                }),

            TextInput::make('unit_price')
                ->label('سعر الوحدة بعد الخصم')
                ->numeric()
                ->prefix('ج.م.')
                ->required()
                ->disabled()
                ->dehydrated(true),

            TextInput::make('total')
                ->label('الإجمالي')
                ->numeric()
                ->prefix('ج.م.')
                ->disabled()
                ->dehydrated(true),

            // حقل مخفي لرقم إصدار القائمة
            Select::make('price_list_version_id')
                ->label('إصدار قائمة الأسعار')
                ->options(PriceListVersion::pluck('version_number', 'id'))
                ->searchable()
                ->dehydrated(true)
                ->hidden(),

        ]);
    }

    // ── Table ────────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('list_price')
                    ->label('سعر اللستة')
                    ->money('EGP'),

                TextColumn::make('discount_1')
                    ->label('خ1%')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%'),

                TextColumn::make('discount_2')
                    ->label('خ2%')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%'),

                TextColumn::make('discount_3')
                    ->label('خ3%')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%'),

                TextColumn::make('unit_price')
                    ->label('سعر الوحدة')
                    ->money('EGP'),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->weight('bold'),
            ])
            ->headerActions(! $this->isReadOnly() ? [
                CreateAction::make()
                    ->label('إضافة صنف')
                    ->after(fn () => $this->triggerRecalc()),
            ] : [])
            ->recordActions(! $this->isReadOnly() ? [
                EditAction::make()
                    ->label('تعديل')
                    ->after(fn () => $this->triggerRecalc()),
                DeleteAction::make()
                    ->label('حذف')
                    ->after(fn () => $this->triggerRecalc()),
            ] : [])
            ->emptyStateHeading('لا توجد بنود')
            ->emptyStateDescription('أضف أصناف الفاتورة');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private static function recalcUnitPrice(mixed $listPrice, callable $get, callable $set): void
    {
        $lp = (float) ($listPrice ?? 0);
        $d1 = (float) ($get('discount_1') ?? 0);
        $d2 = (float) ($get('discount_2') ?? 0);
        $d3 = (float) ($get('discount_3') ?? 0);

        $unitPrice = PriceCalculator::calculateUnitPrice($lp, $d1, $d2, $d3);
        $qty = (float) ($get('quantity') ?? 1);

        $set('unit_price', $unitPrice);
        $set('total', round($qty * $unitPrice, 2));
    }

    private function triggerRecalc(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->getOwnerRecord();
        app(InvoiceService::class)->recalculateTotals($invoice);
    }
}
