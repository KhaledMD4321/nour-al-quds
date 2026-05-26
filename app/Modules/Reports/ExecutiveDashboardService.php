<?php

namespace App\Modules\Reports;

use App\Models\BusinessUnit;
use App\Models\Invoice;
use App\Models\QuickSale;
use App\Models\Treasury;
use Carbon\Carbon;

/**
 * ExecutiveDashboardService — مؤشرات الأداء التنفيذية للوحة التحكم.
 *
 * يبني فوق ReportService (مصدر واحد لحسابات الأرباح/الأعمار) بدل إعادة
 * احتساب الأرقام، فتتطابق مؤشرات اللوحة مع تقرير الأرباح والخسائر.
 */
class ExecutiveDashboardService
{
    /** حالات فاتورة البيع المحتسبة ضمن الإيرادات (متوافقة مع ReportService). */
    private const SALE_STATUSES = ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'];

    public function __construct(private ReportService $reports) {}

    // ════════════════════════════════════════════════════════════════════
    //  لبنات أساسية
    // ════════════════════════════════════════════════════════════════════

    /** إجمالي النقدية في الخزائن النشطة (اختياري: لوحدة معينة). */
    public function cashOnHand(?int $unitId = null): float
    {
        return (float) Treasury::query()
            ->where('is_active', true)
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->sum('current_balance');
    }

    /** إجمالي المبيعات (فواتير + بيع سريع) خلال فترة. */
    public function sales(Carbon $from, Carbon $to, ?int $unitId = null): float
    {
        $invoices = (float) Invoice::query()
            ->where('type', 'sale')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('invoice_date', [$from, $to])
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        $quick = (float) QuickSale::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        return round($invoices + $quick, 2);
    }

    /** عدد العملاء الذين تجاوزوا حد الائتمان (استعلام واحد). */
    public function customersOverCreditLimit(?int $unitId = null): int
    {
        return Invoice::query()
            ->where('invoices.type', 'sale')
            ->whereIn('invoices.status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid'])
            ->where('invoices.payment_type', 'credit')
            ->when($unitId, fn ($q) => $q->where('invoices.business_unit_id', $unitId))
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('customers.credit_limit', '>', 0)
            ->groupBy('invoices.customer_id', 'customers.credit_limit')
            ->havingRaw('SUM(invoices.total_amount - invoices.paid_amount) > customers.credit_limit')
            ->select('invoices.customer_id')
            ->get()
            ->count();
    }

    // ════════════════════════════════════════════════════════════════════
    //  نمو المبيعات — MoM / YoY
    // ════════════════════════════════════════════════════════════════════

    /**
     * مقارنة المبيعات: الشهر الحالي حتى اليوم مقابل نفس الفترة من الشهر/العام السابق.
     *
     * @return array{month: array, year: array}
     */
    public function salesGrowth(?int $unitId = null): array
    {
        $now = today();

        // ── شهري: من بداية الشهر حتى اليوم، مقابل نفس المدى من الشهر الماضي ──
        $monthCurrent = $this->sales($now->copy()->startOfMonth(), $now->copy()->endOfDay(), $unitId);
        $lastMonthSameDay = $now->copy()->subMonthNoOverflow();
        $monthPrevious = $this->sales(
            $lastMonthSameDay->copy()->startOfMonth(),
            $lastMonthSameDay->copy()->endOfDay(),
            $unitId
        );

        // ── سنوي: من بداية السنة حتى اليوم، مقابل نفس المدى من العام الماضي ──
        $yearCurrent = $this->sales($now->copy()->startOfYear(), $now->copy()->endOfDay(), $unitId);
        $lastYearSameDay = $now->copy()->subYearNoOverflow();
        $yearPrevious = $this->sales(
            $lastYearSameDay->copy()->startOfYear(),
            $lastYearSameDay->copy()->endOfDay(),
            $unitId
        );

        return [
            'month' => $this->delta($monthCurrent, $monthPrevious),
            'year' => $this->delta($yearCurrent, $yearPrevious),
        ];
    }

    /** @return array{current: float, previous: float, delta: float, up: bool} */
    private function delta(float $current, float $previous): array
    {
        $delta = $previous > 0
            ? round(($current - $previous) / $previous * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        return [
            'current' => $current,
            'previous' => $previous,
            'delta' => $delta,
            'up' => $current >= $previous,
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    //  الكارت التنفيذي — 6 مؤشرات بإشارة مرور
    // ════════════════════════════════════════════════════════════════════

    /** @return array<int, array> كل عنصر: key,label,display,status,icon,hint */
    public function scorecard(?int $unitId = null): array
    {
        $now = today();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $todayStr = $now->toDateString();

        // الأرباح حتى تاريخه (نفس منطق تقرير الأرباح والخسائر)
        $pl = $this->reports->profitLoss($unitId, $monthStart, $todayStr);

        // النقدية
        $cash = $this->cashOnHand($unitId);

        // نسبة المتأخرات = (المتأخر / إجمالي الذمم) ×100
        $aging = $this->reports->customerAging($unitId);
        $totalAr = (float) $aging->sum('total');
        $overdueAr = (float) $aging->sum(fn ($r) => $r->days_30 + $r->days_60 + $r->days_90 + $r->over_90);
        $arOverduePct = $totalAr > 0 ? round($overdueAr / $totalAr * 100, 1) : 0.0;

        // معدل دوران المخزون = (تكلفة مبيعات آخر 90 يوم سنوياً) ÷ قيمة المخزون الحالية
        $cogs90 = (float) $this->reports->profitLoss(
            $unitId,
            $now->copy()->subDays(90)->toDateString(),
            $todayStr
        )->cost_of_goods;
        $invValue = (float) $this->reports->stockBalance(null, $unitId)->sum('total_value');
        $turnover = $invValue > 0 ? round(($cogs90 * (365 / 90)) / $invValue, 1) : null;

        // مخاطر الائتمان
        $creditOver = $this->customersOverCreditLimit($unitId);

        return [
            [
                'key' => 'cash',
                'label' => 'السيولة النقدية',
                'display' => number_format($cash, 2).' ج.م',
                'status' => $cash < 0 ? 'red' : ($cash == 0.0 ? 'amber' : 'green'),
                'icon' => '💵',
                'hint' => 'إجمالي الخزائن النشطة',
            ],
            [
                'key' => 'net_profit',
                'label' => 'صافي ربح الشهر',
                'display' => number_format($pl->net_profit, 2).' ج.م',
                'status' => $pl->net_profit > 0 ? 'green' : ($pl->net_profit < 0 ? 'red' : 'amber'),
                'icon' => '📈',
                'hint' => $now->translatedFormat('F Y'),
            ],
            [
                'key' => 'gross_margin',
                'label' => 'هامش الربح الإجمالي',
                'display' => number_format((float) $pl->gross_margin, 1).'%',
                'status' => $pl->gross_margin >= 20 ? 'green' : ($pl->gross_margin >= 10 ? 'amber' : 'red'),
                'icon' => '🧮',
                'hint' => 'الإيراد − التكلفة',
            ],
            [
                'key' => 'ar_overdue',
                'label' => 'نسبة المتأخرات',
                'display' => number_format($arOverduePct, 1).'%',
                'status' => $arOverduePct <= 10 ? 'green' : ($arOverduePct <= 25 ? 'amber' : 'red'),
                'icon' => '⏰',
                'hint' => 'من إجمالي ذمم العملاء',
            ],
            [
                'key' => 'inventory_turnover',
                'label' => 'معدل دوران المخزون',
                'display' => $turnover === null ? '—' : number_format($turnover, 1).'×',
                'status' => $turnover === null ? 'gray' : ($turnover >= 4 ? 'green' : ($turnover >= 2 ? 'amber' : 'red')),
                'icon' => '🔄',
                'hint' => 'سنوياً',
            ],
            [
                'key' => 'credit_risk',
                'label' => 'تجاوزوا حد الائتمان',
                'display' => (string) $creditOver.' عميل',
                'status' => $creditOver === 0 ? 'green' : ($creditOver <= 2 ? 'amber' : 'red'),
                'icon' => '⚠️',
                'hint' => 'رصيدهم الآجل فوق الحد',
            ],
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    //  مقارنة الوحدتين — المعرض مقابل التوزيع
    // ════════════════════════════════════════════════════════════════════

    /** @return array<int, array> صف لكل وحدة تشغيلية. */
    public function unitComparison(?string $fromDate = null, ?string $toDate = null): array
    {
        $from = $fromDate ?? today()->startOfMonth()->toDateString();
        $to = $toDate ?? today()->toDateString();

        return BusinessUnit::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function (BusinessUnit $unit) use ($from, $to) {
                $pl = $this->reports->profitLoss($unit->id, $from, $to);

                return [
                    'name' => $unit->name,
                    'is_showroom' => $unit->isShowroom(),
                    'sales' => (float) $pl->net_revenue,
                    'gross_profit' => (float) $pl->gross_profit,
                    'gross_margin' => (float) $pl->gross_margin,
                    'net_profit' => (float) $pl->net_profit,
                    'cash' => $this->cashOnHand($unit->id),
                ];
            })
            ->all();
    }
}
