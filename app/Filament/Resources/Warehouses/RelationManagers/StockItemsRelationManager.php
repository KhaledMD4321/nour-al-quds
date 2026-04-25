<?php

namespace App\Filament\Resources\Warehouses\RelationManagers;

use App\Models\LookupType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;

class StockItemsRelationManager extends RelationManager
{
    protected static string  $relationship     = 'stockItems';
    protected static ?string $title            = 'أرصدة المخزن';
    protected static ?string $modelLabel       = 'رصيد';
    protected static ?string $pluralModelLabel = 'الأرصدة';

    // ─── Form — غير مستخدم (قراءة فقط) ────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ─── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('product.code')
                    ->label('الكود')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('product.name')
                    ->label('الصنف')
                    ->sortable()
                    ->searchable()
                    ->limit(40),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(3)
                    ->sortable()
                    ->color(fn ($record) => $record->quantity <= 0 ? 'danger' : 'success'),

                TextColumn::make('avg_cost')
                    ->label('متوسط التكلفة')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('total_value')
                    ->label('القيمة الإجمالية')
                    ->getStateUsing(fn ($record) => $record->total_value)
                    ->money('EGP'),

                TextColumn::make('product.unit_of_measure')
                    ->label('الوحدة')
                    ->formatStateUsing(fn (?string $state): string =>
                        LookupType::getLabel('unit_of_measure', $state) ?? ($state ?? '—')
                    ),

                TextColumn::make('last_updated')
                    ->label('آخر تحديث')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),

            ])
            ->filters([

                Filter::make('out_of_stock')
                    ->label('نفذ من المخزون')
                    ->query(fn ($query) => $query->where('quantity', '<=', 0))
                    ->toggle(),

            ])
            ->defaultSort('product.name')
            ->paginated([25, 50, 100])
            // ★ قراءة فقط — لا إضافة ولا تعديل ولا حذف
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
