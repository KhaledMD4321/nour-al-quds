<?php

namespace App\Filament\Resources\PurchaseInvoices\Tables;

use App\Models\PurchaseInvoice;
use App\Modules\Purchases\PurchaseService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('reference_number')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('invoice_number')
                    ->label('رقم المورد')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subtotal')
                    ->label('إجمالي البضاعة')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_amount')
                    ->label('الإجمالي الكلي')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'     => 'مسودة',
                        'confirmed' => 'مؤكدة',
                        'paid'      => 'مدفوعة',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'warning',
                        'confirmed' => 'success',
                        'paid'      => 'primary',
                        default     => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft'     => 'مسودة',
                        'confirmed' => 'مؤكدة',
                        'paid'      => 'مدفوعة',
                    ]),

                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name'),

            ])
            ->recordActions([

                EditAction::make()->label('تعديل'),

                // ── تأكيد الاستلام ─────────────────────────────────────────
                Action::make('confirm')
                    ->label('تأكيد الاستلام')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد استلام الفاتورة')
                    ->modalDescription('بعد التأكيد هيتم تحديث المخزون ومش هينفع تعديل البنود. متأكد؟')
                    ->modalSubmitActionLabel('نعم، أكّد الاستلام')
                    ->visible(fn (PurchaseInvoice $record): bool => $record->isDraft())
                    ->action(function (PurchaseInvoice $record): void {
                        try {
                            app(PurchaseService::class)->confirmInvoice($record);
                            Notification::make()
                                ->title('تم تأكيد الفاتورة بنجاح')
                                ->body('تم تحديث المخزون ✓')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('خطأ في التأكيد')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn (PurchaseInvoice $record): bool => $record->isDraft()),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد فواتير مشتريات')
            ->emptyStateDescription('ابدأ بإضافة فاتورة مشتريات جديدة.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }
}
