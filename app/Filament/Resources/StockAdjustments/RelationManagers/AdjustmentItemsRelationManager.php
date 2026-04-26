<?php

namespace App\Filament\Resources\StockAdjustments\RelationManagers;

use App\Models\Lookup;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Modules\Inventory\InventoryService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdjustmentItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title       = 'بنود التسوية';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->label('المنتج')
                ->options(Product::where('is_active', true)->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    /** @var StockAdjustment $adjustment */
                    $adjustment = $this->getOwnerRecord();
                    if ($state && $adjustment->warehouse_id) {
                        $qty = app(InventoryService::class)
                            ->fillExpectedQuantity($adjustment->warehouse_id, (int) $state);
                        $set('expected_quantity', $qty);
                    }
                }),

            TextInput::make('expected_quantity')
                ->label('الكمية المتوقعة (في المخزن)')
                ->disabled()
                ->dehydrated(true)
                ->numeric()
                ->suffix('وحدة'),

            TextInput::make('actual_quantity')
                ->label('الكمية الفعلية (العد)')
                ->required()
                ->numeric()
                ->minValue(0)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $expected = (float) ($get('expected_quantity') ?? 0);
                    $actual   = (float) ($state ?? 0);
                    $set('difference', round($actual - $expected, 3));
                }),

            TextInput::make('difference')
                ->label('الفرق')
                ->disabled()
                ->dehydrated(true)
                ->numeric()
                ->helperText('موجب = زيادة / سالب = نقص'),

            Select::make('reason')
                ->label('سبب البند')
                ->options(function () {
                    return Lookup::where('type', 'adjustment_reason')
                        ->where('is_active', true)
                        ->pluck('label_ar', 'key');
                })
                ->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var StockAdjustment $adjustment */
        $adjustment = $this->getOwnerRecord();
        $isDraft    = $adjustment->isDraft();

        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable(),

                TextColumn::make('expected_quantity')
                    ->label('المتوقعة')
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('actual_quantity')
                    ->label('الفعلية')
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('difference')
                    ->label('الفرق')
                    ->numeric(decimalPlaces: 3)
                    ->badge()
                    ->color(fn ($record): string => match (true) {
                        (float) $record->difference > 0 => 'success',
                        (float) $record->difference < 0 => 'danger',
                        default                          => 'gray',
                    }),

                TextColumn::make('direction_label')
                    ->label('الاتجاه')
                    ->badge()
                    ->color(fn ($record): string => match ($record->direction) {
                        'surplus'  => 'success',
                        'shortage' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(20),
            ])
            ->headerActions($isDraft ? [
                CreateAction::make()->label('إضافة منتج'),
            ] : [])
            ->recordActions($isDraft ? [
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ] : [])
            ->emptyStateHeading('لا توجد بنود بعد')
            ->emptyStateDescription('أضف المنتجات المراد تسويتها');
    }
}
