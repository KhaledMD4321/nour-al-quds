<?php

namespace App\Modules\Reports;

use App\Models\Invoice;
use App\Models\SalesTarget;
use Illuminate\Database\Eloquent\Builder;

/**
 * BudgetService — مبيعات فعلية شهرية وأهداف، للمقارنة بالمستهدف.
 */
class BudgetService
{
    private const REVENUE_STATUSES = ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'];

    /** المبيعات الفعلية لكل شهر في سنة (1..12). @return array<int, float> */
    public function monthlySales(int $year, ?int $unitId = null): array
    {
        $raw = Invoice::query()
            ->where('type', 'sale')
            ->whereIn('status', self::REVENUE_STATUSES)
            ->whereYear('invoice_date', $year)
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->selectRaw('EXTRACT(MONTH FROM invoice_date)::int as m, SUM(total_amount) as t')
            ->groupByRaw('EXTRACT(MONTH FROM invoice_date)')
            ->get();

        $map = [];
        foreach ($raw as $row) {
            $map[(int) $row->m] = (float) $row->t;
        }

        $out = [];
        for ($m = 1; $m <= 12; $m++) {
            $out[$m] = $map[$m] ?? 0.0;
        }

        return $out;
    }

    /** الأهداف المخزّنة لكل شهر في سنة (1..12). @return array<int, float> */
    public function monthlyTargets(int $year, ?int $unitId): array
    {
        $out = [];
        for ($m = 1; $m <= 12; $m++) {
            $out[$m] = 0.0;
        }

        if (! $unitId) {
            return $out;
        }

        SalesTarget::query()
            ->where('business_unit_id', $unitId)
            ->where('year', $year)
            ->get()
            ->each(function (SalesTarget $t) use (&$out) {
                $out[$t->month] = (float) $t->target_amount;
            });

        return $out;
    }

    /**
     * صفوف المقارنة: الشهر، المستهدف، الفعلي، الفرق، نسبة التحقيق.
     *
     * @param  array<int, float>|null  $targets  أهداف مُمرّرة (للعرض الحيّ) أو من القاعدة
     * @return array<int, object>
     */
    public function comparison(int $year, ?int $unitId, ?array $targets = null): array
    {
        $actuals = $this->monthlySales($year, $unitId);
        $targets ??= $this->monthlyTargets($year, $unitId);

        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $target = (float) ($targets[$m] ?? 0);
            $actual = (float) ($actuals[$m] ?? 0);

            $rows[] = (object) [
                'month' => $m,
                'target' => round($target, 2),
                'actual' => round($actual, 2),
                'variance' => round($actual - $target, 2),
                'achievement' => $target > 0 ? round($actual / $target * 100, 1) : null,
            ];
        }

        return $rows;
    }
}
