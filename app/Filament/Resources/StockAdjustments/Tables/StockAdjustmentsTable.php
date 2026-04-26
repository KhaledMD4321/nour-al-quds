<?php

namespace App\Filament\Resources\StockAdjustments\Tables;

use App\Models\StockAdjustment;
use App\Modules\Inventory\InventoryService;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('المرجع')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('adjustment_date')
                    ->label('تاريخ التسوية')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('عدد البنود')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('status_label')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (StockAdjustment $record): string => match ($record->status) {
                        'draft'     => 'warning',
                        'confirmed' => 'success',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft'     => 'مسودة',
                        'confirmed' => 'مؤكد',
                    ]),

                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name'),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('تأكيد التسوية')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StockAdjustment $record): bool => $record->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد تسوية المخزون')
                    ->modalDescription('هل أنت متأكد؟ سيتم تعديل أرصدة المخزون ولا يمكن التراجع.')
                    ->action(function (StockAdjustment $record): void {
                        app(InventoryService::class)->confirmAdjustment($record);
                        \Filament\Notifications\Notification::make()
                            ->title('تم تأكيد التسوية بنجاح')
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('تعديل'),

                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn (StockAdjustment $record): bool => $record->isDraft()),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
    }
}
