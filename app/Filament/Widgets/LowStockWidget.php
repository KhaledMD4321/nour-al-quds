<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'أصناف تحت الحد الأدنى';

    // عتبة الحد الأدنى الافتراضية (لأن products.min_stock_level غير موجود في قاعدة البيانات بعد)
    private const DEFAULT_MIN = 5;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $unitId = ($user && ! $user->isSuperAdmin() && $user->business_unit_id)
            ? $user->business_unit_id : null;

        return $table
            ->query(
                Stock::with(['product', 'warehouse'])
                    ->join('products', 'products.id', '=', 'stock.product_id')
                    ->whereRaw('stock.quantity <= ?', [self::DEFAULT_MIN])
                    ->where('stock.quantity', '>', 0)
                    ->whereNull('products.deleted_at')
                    ->when(
                        $unitId,
                        fn (Builder $q) => $q->whereHas(
                            'warehouse',
                            fn (Builder $w) => $w->where('business_unit_id', $unitId)
                        )
                    )
                    ->select('stock.*')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.code')
                    ->label('الكود')
                    ->fontFamily('mono')
                    ->width('80px'),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('الصنف')
                    ->limit(35),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('المخزن'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية الحالية')
                    ->numeric(decimalPlaces: 2)
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->paginated([5, 10])
            ->defaultSort('quantity', 'asc')
            ->emptyStateHeading('كل الأصناف فوق الحد الأدنى ✓')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
