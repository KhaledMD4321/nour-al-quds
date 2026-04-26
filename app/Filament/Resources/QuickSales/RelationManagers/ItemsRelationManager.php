<?php

namespace App\Filament\Resources\QuickSales\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string  $relationship = 'items';
    protected static ?string $title        = 'بنود الإيصال';

    // قراءة فقط — لا إضافة ولا تعديل ولا حذف
    public function isReadOnly(): bool { return true; }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('الصنف')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('unit_price')
                    ->label('السعر / وحدة')
                    ->money('EGP'),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP'),
            ])
            ->emptyStateHeading('لا توجد بنود');
    }
}
