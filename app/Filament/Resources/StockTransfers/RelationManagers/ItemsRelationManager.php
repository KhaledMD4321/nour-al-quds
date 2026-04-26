<?php

namespace App\Filament\Resources\StockTransfers\RelationManagers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title       = 'بنود التحويل';

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
                    /** @var StockTransfer $transfer */
                    $transfer = $this->getOwnerRecord();
                    if ($state && $transfer->from_warehouse_id) {
                        $stock = Stock::where('warehouse_id', $transfer->from_warehouse_id)
                            ->where('product_id', $state)
                            ->first();
                        $set('unit_cost', $stock ? (float) $stock->avg_cost : 0);
                        $set('_available', $stock ? (float) $stock->quantity : 0);
                    }
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
                ->live(onBlur: true),

            TextInput::make('unit_cost')
                ->label('التكلفة / وحدة')
                ->disabled()
                ->dehydrated(false)
                ->numeric()
                ->prefix('ج.م'),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var StockTransfer $transfer */
        $transfer = $this->getOwnerRecord();
        $isDraft  = $transfer->isDraft();

        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('unit_cost')
                    ->label('التكلفة / وحدة')
                    ->money('EGP'),
            ])
            ->headerActions($isDraft ? [
                Tables\Actions\CreateAction::make()->label('إضافة منتج'),
            ] : [])
            ->recordActions($isDraft ? [
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ] : [])
            ->emptyStateHeading('لا توجد بنود بعد')
            ->emptyStateDescription('أضف المنتجات المراد تحويلها');
    }
}
