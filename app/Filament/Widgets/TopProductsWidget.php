<?php

namespace App\Filament\Widgets;

use App\Models\InvoiceItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopProductsWidget extends TableWidget
{
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'أكثر 10 أصناف مبيعاً هذا الشهر';

    public function table(Table $table): Table
    {
        $user   = auth()->user();
        $unitId = ($user && !$user->isSuperAdmin() && $user->business_unit_id)
            ? $user->business_unit_id : null;

        return $table
            ->query(
                InvoiceItem::query()
                    ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_value')
                    ->whereHas('invoice', function (Builder $q) use ($unitId) {
                        $q->where('type', 'sale')
                          ->whereNotIn('status', ['draft', 'cancelled'])
                          ->whereMonth('invoice_date', today()->month)
                          ->whereYear('invoice_date', today()->year)
                          ->when($unitId, fn ($q2) => $q2->where('business_unit_id', $unitId));
                    })
                    ->groupBy('product_id')
                    ->orderByDesc('total_value')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.code')
                    ->label('الكود')
                    ->fontFamily('mono')
                    ->width('80px'),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('الصنف')
                    ->limit(40),

                Tables\Columns\TextColumn::make('total_qty')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 2)
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('الإيراد')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' ج.م')
                    ->weight('bold')
                    ->color('success'),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد مبيعات هذا الشهر')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}
