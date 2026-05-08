<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\StockMovement;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'حركات المخزون';
    protected static ?string $modelLabel      = 'حركة مخزون';
    protected static ?string $pluralModelLabel = 'حركات المخزون';
    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';
    protected static ?int    $navigationSort  = 3;

    // ── Read-only resource ───────────────────────────────────────────────────

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    // ── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type_label')
                    ->label('نوع الحركة')
                    ->badge()
                    ->color(fn (StockMovement $record): string => match ($record->type) {
                        'in', 'transfer_in', 'adjustment_plus', 'opening' => 'success',
                        'out', 'transfer_out', 'adjustment_minus'          => 'danger',
                        default                                             => 'gray',
                    }),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),

                TextColumn::make('unit_cost')
                    ->label('التكلفة / وحدة')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('balance_after')
                    ->label('الرصيد بعد')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name'),

                SelectFilter::make('type')
                    ->label('نوع الحركة')
                    ->options([
                        'in'               => 'دخول',
                        'out'              => 'خروج',
                        'transfer_in'      => 'تحويل وارد',
                        'transfer_out'     => 'تحويل صادر',
                        'adjustment_plus'  => 'تسوية بالزيادة',
                        'adjustment_minus' => 'تسوية بالنقص',
                        'opening'          => 'رصيد افتتاحي',
                    ]),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('لا توجد حركات مخزون')
            ->emptyStateDescription('لا توجد حركات مخزون مسجّلة.')
            ->emptyStateIcon('heroicon-o-arrow-path')
            ->striped();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
