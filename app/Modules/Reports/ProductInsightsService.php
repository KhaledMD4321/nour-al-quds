<?php

namespace App\Modules\Reports;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * ProductInsightsService — تحليل الأصناف.
 *   - تصنيف ABC حسب مساهمة الإيراد (A: أول 80% · B: 80–95% · C: الباقي)
 *   - اقتراحات إعادة الطلب (أصناف تحت الحد الأدنى + سرعة البيع)
 */
class ProductInsightsService
{
    public function __construct(private ReportService $reports) {}

    /** تصنيف ABC للأصناف حسب الإيراد التراكمي. */
    public function abcAnalysis(string $fromDate, string $toDate, ?int $unitId = null): Collection
    {
        $rows = $this->reports->salesByProduct($fromDate, $toDate, $unitId); // مرتّبة تنازلياً بالإيراد
        $grand = (float) $rows->sum('total_revenue');
        $cumulative = 0.0;

        return $rows->map(function ($r) use (&$cumulative, $grand) {
            $cumulative += (float) $r->total_revenue;
            $cumPct = $grand > 0 ? round($cumulative / $grand * 100, 1) : 0.0;
            $class = $cumPct <= 80 ? 'A' : ($cumPct <= 95 ? 'B' : 'C');

            return (object) [
                'product_code' => $r->product_code,
                'product_name' => $r->product_name,
                'qty' => (float) $r->total_qty,
                'revenue' => round((float) $r->total_revenue, 2),
                'cumulative_pct' => $cumPct,
                'class' => $class,
            ];
        });
    }

    /** اقتراحات إعادة الطلب: أصناف رصيدها ≤ الحد الأدنى، مع سرعة البيع. */
    public function reorderSuggestions(int $velocityDays = 30, ?int $unitId = null): Collection
    {
        $since = today()->subDays($velocityDays);

        $sold = StockMovement::where('type', 'out')
            ->where('reference_type', 'invoice')
            ->where('created_at', '>=', $since)
            ->when($unitId, fn (Builder $q) => $q->whereHas('warehouse', fn ($w) => $w->where('business_unit_id', $unitId)))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as sold')
            ->pluck('sold', 'product_id');

        $stockQuery = Stock::with(['product.company'])->where('quantity', '>=', 0);
        if ($unitId) {
            $stockQuery->whereHas('warehouse', fn (Builder $q) => $q->where('business_unit_id', $unitId));
        }

        return $stockQuery->get()
            ->groupBy('product_id')
            ->map(function ($rows) use ($sold, $velocityDays) {
                $product = $rows->first()->product;
                $min = (float) $product->min_stock_level;

                // فقط الأصناف التي لها حدّ أدنى ورصيدها وصله أو أقل
                if ($min <= 0) {
                    return null;
                }
                $current = (float) $rows->sum('quantity');
                if ($current > $min) {
                    return null;
                }

                $soldQty = (float) ($sold[$product->id] ?? 0);
                $velocity = round($soldQty / $velocityDays, 2);                  // باليوم
                $daysCover = $velocity > 0 ? (int) floor($current / $velocity) : null;
                $target = max((int) ceil($velocity * 30), (int) ceil($min));     // تغطية شهر أو الحد الأدنى
                $suggested = max(0, $target - (int) round($current));

                return (object) [
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'manufacturer' => $product->company?->name ?? 'بدون مصنّع',
                    'current_stock' => $current,
                    'min_level' => $min,
                    'sold' => $soldQty,
                    'velocity' => $velocity,
                    'days_cover' => $daysCover,
                    'suggested_order' => $suggested,
                ];
            })
            ->filter()
            ->sortBy(fn ($r) => $r->days_cover ?? PHP_INT_MAX)
            ->values();
    }
}
