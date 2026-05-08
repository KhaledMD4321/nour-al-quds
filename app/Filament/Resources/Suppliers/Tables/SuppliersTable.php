<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->copyable(),

                TextColumn::make('name')
                    ->label('اسم المورد')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(35),

                TextColumn::make('phone')
                    ->label('التليفون')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('company.name')
                    ->label('المصنّع')
                    ->placeholder('غير محدد')
                    ->sortable(),

                TextColumn::make('opening_balance')
                    ->label('رصيد افتتاحي')
                    ->money('EGP')
                    ->sortable()
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'gray'),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                SelectFilter::make('company_id')
                    ->label('المصنّع')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('الكل'),

                TernaryFilter::make('has_balance')
                    ->label('الرصيد')
                    ->placeholder('الكل')
                    ->trueLabel('عليه رصيد')
                    ->falseLabel('بدون رصيد')
                    ->queries(
                        true:  fn ($query) => $query->where('opening_balance', '>', 0),
                        false: fn ($query) => $query->where('opening_balance', '<=', 0),
                    ),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),

                TrashedFilter::make(),

            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
                RestoreAction::make()->label('استعادة'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                    ForceDeleteBulkAction::make()->label('حذف نهائي'),
                    RestoreBulkAction::make()->label('استعادة'),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('لا يوجد موردون')
            ->emptyStateDescription('ابدأ بإضافة مورد جديد.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }
}
