<?php

namespace App\Modules\Reports;

use App\Models\Invoice;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ReturnsAnalysisService — تحليل مرتجعات المبيعات.
 *   - نسبة المرتجع الإجمالية (مرتجعات ÷ مبيعات)
 *   - الأصناف الأكثر إرجاعاً ونسبة إرجاع كل صنف
 *   - اتجاه شهري لنسبة المرتجع
 */
class ReturnsAnalysisService
{
    private const REVENUE_STATUSES = ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'];

    private function total(string $type, Carbon $from, Carbon $to, ?int $unitId): float
    {
        return (float) Invoice::query()
            ->where('type', $type)
            ->whereIn('status', self::REVENUE_STATUSES)
            ->whereBetween('invoice_date', [$from, $to])
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');
    }

    /** @return object{sales: float, returns: float, count: int, rate: float} */
    public function summary(string $fromDate, string $toDate, ?int $unitId = null): object
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $sales = $this->total('sale', $from, $to, $unitId);
        $returns = $this->total('sale_return', $from, $to, $unitId);

        $count = (int) Invoice::query()
            ->where('type', 'sale_return')
            ->whereIn('status', self::REVENUE_STATUSES)
            ->whereBetween('invoice_date', [$from, $to])
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->count();

        return (object) [
            'sales' => round($sales, 2),
            'returns' => round($returns, 2),
            'count' => $count,
            'rate' => $sales > 0 ? round($returns / $sales * 100, 1) : 0.0,
        ];
    }

    /** قيمة لكل صنف من نوع فاتورة معيّن. */
    private function perProduct(string $type, Carbon $from, Carbon $to, ?int $unitId): Collection
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.type', $type)
            ->whereIn('invoices.status', self::REVENUE_STATUSES)
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->when($unitId, fn ($q) => $q->where('invoices.business_unit_id', $unitId))
            ->groupBy('invoice_items.product_id')
            ->selectRaw('invoice_items.product_id as pid, SUM(invoice_items.total) as val')
            ->pluck('val', 'pid');
    }

    /** الأصناف الأكثر إرجاعاً مع نسبة الإرجاع لكل صنف. */
    public function byProduct(string $fromDate, string $toDate, ?int $unitId = null): Collection
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $sales = $this->perProduct('sale', $from, $to, $unitId);
        $returns = $this->perProduct('sale_return', $from, $to, $unitId);

        $products = Product::whereIn('id', $returns->keys())->get(['id', 'code', 'name'])->keyBy('id');

        return $returns->map(function ($returnVal, $pid) use ($sales, $products) {
            $salesVal = (float) ($sales[$pid] ?? 0);
            $ret = (float) $returnVal;

            return (object) [
                'product_code' => $products[$pid]->code ?? '—',
                'product_name' => $products[$pid]->name ?? '—',
                'sales' => round($salesVal, 2),
                'returns' => round($ret, 2),
                'rate' => $salesVal > 0 ? round($ret / $salesVal * 100, 1) : 0.0,
            ];
        })->sortByDesc('returns')->values();
    }

    /** اتجاه شهري لنسبة المرتجع لآخر N شهر. */
    public function monthlyTrend(int $months = 6, ?int $unitId = null): Collection
    {
        $rows = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = today()->subMonthsNoOverflow($i);
            $from = $month->copy()->startOfMonth();
            $to = $month->copy()->endOfMonth();

            $sales = $this->total('sale', $from, $to, $unitId);
            $returns = $this->total('sale_return', $from, $to, $unitId);

            $rows->push((object) [
                'month' => $month->translatedFormat('M Y'),
                'sales' => round($sales, 2),
                'returns' => round($returns, 2),
                'rate' => $sales > 0 ? round($returns / $sales * 100, 1) : 0.0,
            ]);
        }

        return $rows;
    }
}
