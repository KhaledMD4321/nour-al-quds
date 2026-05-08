<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use App\Models\BusinessUnit;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('المخزن')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة التشغيلية')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) =>
                        $record->businessUnit?->type === BusinessUnit::TYPE_SHOWROOM
                            ? 'info'
                            : 'success'
                    ),

                TextColumn::make('location')
                    ->label('الموقع')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('stock_items_count')
                    ->label('عدد الأصناف')
                    ->counts('stockItems')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                SelectFilter::make('business_unit_id')
                    ->label('الوحدة التشغيلية')
                    ->relationship('businessUnit', 'name'),

                SelectFilter::make('is_active')
                    ->label('الحالة')
                    ->options([
                        '1' => 'نشط',
                        '0' => 'غير نشط',
                    ])
                    ->placeholder('الكل'),

                TrashedFilter::make(),

            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),

                DeleteAction::make()
                    ->label('حذف')
                    ->using(function ($record): void {
                        try {
                            $record->delete();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                RestoreAction::make()->label('استعادة'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                    ForceDeleteBulkAction::make()->label('حذف نهائي'),
                    RestoreBulkAction::make()->label('استعادة'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد مستودعات')
            ->emptyStateDescription('ابدأ بإضافة مستودع جديد.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }
}
