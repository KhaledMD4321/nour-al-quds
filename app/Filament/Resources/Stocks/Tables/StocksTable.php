<?php

namespace App\Filament\Resources\Stocks\Tables;

use App\Models\LookupType;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->sortable()
                    ->searchable(),

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

                TextColumn::make('product.company.name')
                    ->label('المصنّع')
                    ->sortable()
                    ->toggleable(),

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
                    ->money('EGP')
                    ->sortable(query: fn ($query, string $direction) => $query->orderByRaw("quantity * avg_cost {$direction}")
                    ),

                TextColumn::make('product.unit_of_measure')
                    ->label('الوحدة')
                    ->formatStateUsing(fn (?string $state): string => LookupType::getLabel('unit_of_measure', $state) ?? ($state ?? '—')
                    ),

                TextColumn::make('last_updated')
                    ->label('آخر تحديث')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('out_of_stock')
                    ->label('نفذ من المخزون')
                    ->query(fn ($query) => $query->where('quantity', '<=', 0))
                    ->toggle(),

                Filter::make('product_name')
                    ->form([
                        FormTextInput::make('name')
                            ->label('اسم الصنف')
                            ->placeholder('اكتب جزء من الاسم…'),
                    ])
                    ->query(fn ($query, array $data) => $query->when($data['name'] ?? null,
                        fn ($q, $name) => $q->whereHas('product',
                            fn ($pq) => $pq->where('name', 'ILIKE', "%{$name}%")
                        )
                    )
                    )
                    ->indicateUsing(fn (array $data): ?string => filled($data['name'] ?? null) ? 'صنف: '.$data['name'] : null
                    ),

            ])
            // ★ قراءة فقط — لا actions
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('product.name')
            ->striped()
            ->emptyStateHeading('لا توجد أرصدة حالياً')
            ->emptyStateDescription('الأرصدة هتظهر تلقائي لما تبدأ عمليات الشراء والبيع')
            ->emptyStateIcon('heroicon-o-archive-box');
    }
}
