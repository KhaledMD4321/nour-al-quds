<?php

namespace App\Filament\Resources\StockTransfers\Tables;

use App\Models\StockTransfer;
use App\Modules\Inventory\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTransfersTable
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

                TextColumn::make('transfer_date')
                    ->label('تاريخ التحويل')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('fromWarehouse.name')
                    ->label('من مخزن')
                    ->sortable(),

                TextColumn::make('toWarehouse.name')
                    ->label('إلى مخزن')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('عدد البنود')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status_label')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (StockTransfer $record): string => match ($record->status) {
                        'draft' => 'warning',
                        'confirmed' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('createdBy.name')
                    ->label('أنشأ بواسطة')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'confirmed' => 'مؤكد',
                    ]),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('تأكيد التحويل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StockTransfer $record): bool => $record->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد تحويل المخزون')
                    ->modalDescription('هل أنت متأكد؟ سيتم تحديث أرصدة المخزون ولا يمكن التراجع.')
                    ->action(function (StockTransfer $record): void {
                        app(InventoryService::class)->confirmTransfer($record);
                        Notification::make()
                            ->title('تم تأكيد التحويل بنجاح')
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('تعديل'),

                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn (StockTransfer $record): bool => $record->isDraft()),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('لا توجد تحويلات مخزون')
            ->emptyStateDescription('ابدأ بإضافة تحويل جديد.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }
}
