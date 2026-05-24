<?php

namespace App\Filament\Resources\UnitTransfers\RelationManagers;

use App\Models\PriceListVersion;
use App\Models\Product;
use App\Models\Stock;
use App\Models\UnitTransfer;
use App\Modules\Sales\UnitTransferService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'بنود التحويل';

    // ── للقراءة فقط إذا كان التحويل مؤكداً ──────────────────────────────────────

    public function isReadOnly(): bool
    {
        /** @var UnitTransfer $transfer */
        $transfer = $this->getOwnerRecord();

        return $transfer->isConfirmed();
    }

    // ── Form ─────────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('product_id')
                ->label('الصنف')
                ->required()
                ->searchable()
                ->live()
                ->options(fn (): array => Product::where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray()
                )
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    if (! $state) {
                        return;
                    }

                    /** @var UnitTransfer $transfer */
                    $transfer = $this->getOwnerRecord();

                    $unitPrice = $this->resolveUnitPrice(
                        (int) $state,
                        $transfer->transfer_price_type,
                        $transfer->from_warehouse_id
                    );

                    $set('unit_price', $unitPrice);

                    // عرض الكمية المتاحة
                    $available = (float) (Stock::where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('product_id', $state)
                        ->value('quantity') ?? 0);
                    $set('_available_qty', $available);

                    // تحديث الإجمالي
                    $qty = (float) ($get('quantity') ?? 0);
                    $set('total', round($qty * $unitPrice, 2));
                })
                ->columnSpanFull(),

            Placeholder::make('_available_qty')
                ->label('الكمية المتاحة في المصدر')
                ->content(function (callable $get): string {
                    $qty = $get('_available_qty');

                    return $qty !== null ? number_format((float) $qty, 3) : '—';
                }),

            TextInput::make('quantity')
                ->label('الكمية')
                ->numeric()
                ->required()
                ->minValue(0.001)
                ->step(0.001)
                ->default(1)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    $set('total', round((float) $state * (float) ($get('unit_price') ?? 0), 2));
                }),

            TextInput::make('unit_price')
                ->label('سعر التحويل')
                ->numeric()
                ->required()
                ->prefix('ج.م.')
                ->minValue(0)
                ->step(0.0001)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    $set('total', round((float) ($get('quantity') ?? 0) * (float) $state, 2));
                }),

            TextInput::make('total')
                ->label('الإجمالي')
                ->prefix('ج.م.')
                ->numeric()
                ->disabled()
                ->dehydrated(),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('الصنف')
                    ->searchable()
                    ->limit(45),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('unit_price')
                    ->label('سعر التحويل')
                    ->money('EGP'),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->weight('bold'),
            ])
            ->headerActions(! $this->isReadOnly() ? [
                CreateAction::make()
                    ->label('إضافة صنف')
                    ->after(fn () => $this->recalcTotals()),
            ] : [])
            ->recordActions(! $this->isReadOnly() ? [
                EditAction::make()
                    ->label('تعديل')
                    ->after(fn () => $this->recalcTotals()),
                DeleteAction::make()
                    ->label('حذف')
                    ->after(fn () => $this->recalcTotals()),
            ] : [])
            ->emptyStateHeading('لا توجد بنود')
            ->emptyStateDescription('أضف الأصناف المراد تحويلها من زرار "إضافة صنف" أعلاه');
    }

    // ── Mutate ────────────────────────────────────────────────────────────────────

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total'] = round(
            (float) ($data['quantity'] ?? 0) * (float) ($data['unit_price'] ?? 0),
            2
        );
        unset($data['_available_qty']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total'] = round(
            (float) ($data['quantity'] ?? 0) * (float) ($data['unit_price'] ?? 0),
            2
        );
        unset($data['_available_qty']);

        return $data;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────────

    /**
     * تحديد سعر التحويل تلقائياً بناءً على نوع التسعير.
     */
    private function resolveUnitPrice(int $productId, string $priceType, ?int $warehouseId): float
    {
        return match ($priceType) {
            'avg_cost' => $this->getAvgCost($productId, $warehouseId),
            'list_price' => $this->getListPrice($productId),
            default => 0.0, // custom — المستخدم يدخله يدوياً
        };
    }

    private function getAvgCost(int $productId, ?int $warehouseId): float
    {
        if (! $warehouseId) {
            return 0.0;
        }

        return (float) (Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('avg_cost') ?? 0);
    }

    private function getListPrice(int $productId): float
    {
        // الإصدار النشط من قائمة الأسعار
        $activeVersion = PriceListVersion::where('status', 'active')
            ->orderByDesc('effective_date')
            ->first();

        if (! $activeVersion) {
            return 0.0;
        }

        return (float) ($activeVersion->items()
            ->where('product_id', $productId)
            ->value('price') ?? 0);
    }

    private function recalcTotals(): void
    {
        /** @var UnitTransfer $transfer */
        $transfer = $this->getOwnerRecord();
        app(UnitTransferService::class)->recalculateTotals($transfer);
    }
}
