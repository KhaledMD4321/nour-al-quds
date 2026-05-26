<?php

namespace App\Modules\Reports;

use App\Models\Invoice;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CollectionsService — مؤشرات صحّة التحصيل والذمم المدينة.
 *   - DSO (متوسط فترة التحصيل)
 *   - معدل التحصيل في الفترة
 *   - لقطة أعمار الديون الحالية (يعيد استخدام ReportService)
 *   - اتجاه شهري: مبيعات مقابل تحصيلات
 */
class CollectionsService
{
    /** حالات فاتورة بيع تُحتسب ضمن الإيراد. */
    private const SALE_REVENUE_STATUSES = ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'];

    /** حالات فاتورة بيع مفتوحة (محتاجة تحصيل). */
    private const OPEN_SALE_STATUSES = ['confirmed', 'delivered', 'partial_paid', 'partially_paid'];

    public function __construct(private ReportService $reports) {}

    /** الذمم المدينة المفتوحة الحالية (إجمالي غير المحصّل). */
    public function openReceivables(?int $unitId = null): float
    {
        return (float) Invoice::query()
            ->where('type', 'sale')
            ->whereIn('status', self::OPEN_SALE_STATUSES)
            ->whereRaw('total_amount > paid_amount')
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->sum(DB::raw('total_amount - paid_amount'));
    }

    /** صافي المبيعات خلال فترة (مبيعات − مرتجعات). */
    public function netSales(Carbon $from, Carbon $to, ?int $unitId = null): float
    {
        $sales = (float) Invoice::query()
            ->where('type', 'sale')
            ->whereIn('status', self::SALE_REVENUE_STATUSES)
            ->whereBetween('invoice_date', [$from, $to])
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        $returns = (float) Invoice::query()
            ->where('type', 'sale_return')
            ->whereIn('status', self::SALE_REVENUE_STATUSES)
            ->whereBetween('invoice_date', [$from, $to])
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        return round($sales - $returns, 2);
    }

    /** التحصيلات (سندات القبض) خلال فترة. */
    public function collections(Carbon $from, Carbon $to, ?int $unitId = null): float
    {
        return (float) Receipt::query()
            ->whereBetween('receipt_date', [$from, $to])
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->sum('amount');
    }

    /**
     * ملخص التحصيل: الذمم، DSO، معدل التحصيل، المتأخر.
     *
     * @return object{ar: float, sales: float, collections: float, days: int, dso: int, collection_rate: float, overdue: float}
     */
    public function summary(string $fromDate, string $toDate, ?int $unitId = null): object
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();
        $days = max(1, (int) $from->diffInDays($to) + 1);

        $ar = $this->openReceivables($unitId);
        $sales = $this->netSales($from, $to, $unitId);
        $collections = $this->collections($from, $to, $unitId);

        $buckets = $this->agingBuckets($unitId);

        return (object) [
            'ar' => round($ar, 2),
            'sales' => $sales,
            'collections' => round($collections, 2),
            'days' => $days,
            // DSO = الذمم ÷ (المبيعات اليومية)
            'dso' => $sales > 0 ? (int) round($ar / ($sales / $days)) : 0,
            'collection_rate' => $sales > 0 ? round($collections / $sales * 100, 1) : 0.0,
            'overdue' => round($buckets->days_30 + $buckets->days_60 + $buckets->days_90 + $buckets->over_90, 2),
        ];
    }

    /** لقطة أعمار الديون الحالية مجمّعة. */
    public function agingBuckets(?int $unitId = null): object
    {
        $aging = $this->reports->customerAging($unitId);

        return (object) [
            'current' => round((float) $aging->sum('current'), 2),
            'days_30' => round((float) $aging->sum('days_30'), 2),
            'days_60' => round((float) $aging->sum('days_60'), 2),
            'days_90' => round((float) $aging->sum('days_90'), 2),
            'over_90' => round((float) $aging->sum('over_90'), 2),
            'total' => round((float) $aging->sum('total'), 2),
        ];
    }

    /** اتجاه شهري: مبيعات مقابل تحصيلات لآخر N شهر. */
    public function monthlyTrend(int $months = 6, ?int $unitId = null): Collection
    {
        $rows = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = today()->subMonthsNoOverflow($i);
            $from = $month->copy()->startOfMonth();
            $to = $month->copy()->endOfMonth();

            $sales = $this->netSales($from, $to, $unitId);
            $collected = $this->collections($from, $to, $unitId);

            $rows->push((object) [
                'month' => $month->translatedFormat('M Y'),
                'sales' => $sales,
                'collections' => round($collected, 2),
                'gap' => round($sales - $collected, 2),
            ]);
        }

        return $rows;
    }
}
