<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\LookupType;
use App\Services\CustomFieldRenderer;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Select as FormSelect;

class CustomersTable
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
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(35),

                TextColumn::make('phone')
                    ->label('التليفون')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn (?string $state): string =>
                        LookupType::getLabel('customer_type', $state) ?? ($state ?? '—')
                    )
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individual' => 'info',
                        'company'    => 'primary',
                        'trader'     => 'success',
                        'contractor' => 'warning',
                        'government' => 'danger',
                        default      => 'gray',
                    }),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->placeholder('عام')
                    ->toggleable(),

                TextColumn::make('credit_limit')
                    ->label('حد الائتمان')
                    ->money('EGP')
                    ->sortable()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray'),

                TextColumn::make('effective_discount_percent')
                    ->label('خصم فعلي')
                    ->getStateUsing(fn ($record): string => $record->effective_discount_percent . '%')
                    ->sortable(query: fn ($query, string $direction) =>
                        $query->orderByRaw(
                            "(1 - (1-default_discount_1/100) * (1-default_discount_2/100) * (1-default_discount_3/100)) {$direction}"
                        )
                    )
                    ->toggleable(),

                TextColumn::make('opening_balance')
                    ->label('رصيد افتتاحي')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ─── حقول مخصصة قابلة للبحث ───
                ...CustomFieldRenderer::tableColumns('customer'),

            ])
            ->filters([

                SelectFilter::make('type')
                    ->label('نوع العميل')
                    ->options(fn (): array => LookupType::getOptions('customer_type')),

                SelectFilter::make('business_unit_id')
                    ->label('الوحدة التشغيلية')
                    ->relationship('businessUnit', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('has_credit')
                    ->label('عنده ائتمان')
                    ->query(fn ($query) => $query->where('credit_limit', '>', 0))
                    ->toggle(),

                Filter::make('is_active')
                    ->label('نشط فقط')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->toggle(),

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
            ->emptyStateHeading('لا يوجد عملاء')
            ->emptyStateDescription('ابدأ بإضافة عميل جديد.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }
}
