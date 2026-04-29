<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('payment_type')
                    ->label('الدفع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash'   => 'نقدي',
                        'credit' => 'آجل',
                        'cheque' => 'شيك',
                        default  => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash'   => 'success',
                        'credit' => 'warning',
                        'cheque' => 'info',
                        default  => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Invoice::statusLabel($state))
                    ->color(fn (string $state): string => Invoice::statusColor($state)),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->state(fn (Invoice $record): float => $record->remaining_amount)
                    ->money('EGP')
                    ->color(fn (Invoice $record): string => $record->remaining_amount > 0 ? 'danger' : 'success'),

                TextColumn::make('createdBy.name')
                    ->label('المحرر')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft'          => 'مسودة',
                        'confirmed'      => 'مؤكدة',
                        'delivered'      => 'مسلّمة',
                        'partially_paid' => 'مدفوعة جزئياً',
                        'paid'           => 'مدفوعة',
                        'cancelled'      => 'ملغاة',
                    ]),

                SelectFilter::make('payment_type')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash'   => 'نقدي',
                        'credit' => 'آجل',
                        'cheque' => 'شيك',
                    ]),

                Filter::make('today')
                    ->label('اليوم')
                    ->query(fn (Builder $query) => $query->whereDate('invoice_date', today())),
            ])
            ->actions([
                Action::make('return')
                    ->label('مرتجع')
                    ->icon('heroicon-o-arrow-uturn-right')
                    ->color('warning')
                    ->visible(fn (Invoice $record): bool =>
                        $record->type === 'sale' &&
                        in_array($record->status, ['confirmed', 'delivered', 'partially_paid', 'paid'])
                    )
                    ->url(fn (Invoice $record): string =>
                        \App\Filament\Pages\SaleReturn::getUrl(['invoice' => $record->id])
                    ),

                ViewAction::make()->label('تفاصيل'),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
    }
}
