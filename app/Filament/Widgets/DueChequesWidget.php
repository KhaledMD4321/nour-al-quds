<?php

namespace App\Filament\Widgets;

use App\Models\Cheque;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DueChequesWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'شيكات مستحقة خلال 7 أيام';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $unitId = ($user && ! $user->isSuperAdmin() && $user->business_unit_id)
            ? $user->business_unit_id : null;

        return $table
            ->query(
                Cheque::with(['customer', 'supplier'])
                    ->whereIn('status', ['pending', 'deposited'])
                    ->whereBetween('due_date', [today(), today()->addDays(7)])
                    ->when(
                        $unitId,
                        fn (Builder $q) => $q->where('business_unit_id', $unitId)
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('cheque_number')
                    ->label('رقم الشيك')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('direction')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'incoming' ? 'وارد' : 'صادر')
                    ->color(fn ($state) => $state === 'incoming' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('party')
                    ->label('العميل / المورد')
                    ->getStateUsing(fn (Cheque $record) => $record->customer?->name ?? $record->supplier?->name ?? '—'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' ج.م')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state <= today() ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'deposited' => 'مودع بالبنك',
                        default => $state,
                    })
                    ->color(fn ($state) => $state === 'deposited' ? 'info' : 'gray'),
            ])
            ->paginated([5])
            ->defaultSort('due_date', 'asc')
            ->emptyStateHeading('مفيش شيكات مستحقة قريباً ✓')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
