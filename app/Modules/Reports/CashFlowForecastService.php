<?php

namespace App\Modules\Reports;

use App\Models\Cheque;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * CashFlowForecastService — توقّع التدفق النقدي للأمام.
 *
 * يسقط على فترات (أسبوعية أو شهرية) المتحصلات والمدفوعات المتوقعة:
 *   داخل  = ذمم العملاء المستحقة + شيكات واردة (حسب تاريخ الاستحقاق)
 *   خارج  = ذمم الموردين المستحقة + شيكات صادرة
 * ثم يحسب الرصيد التراكمي المتوقّع بدءاً من النقدية الحالية،
 * فيظهر متى قد تنخفض السيولة (الرصيد التراكمي سالب).
 */
class CashFlowForecastService
{
    public function __construct(private ExecutiveDashboardService $exec) {}

    /**
     * @param  string  $granularity  week|month
     * @return array{opening_cash: float, granularity: string, buckets: array, total_inflow: float, total_outflow: float, ending_balance: float}
     */
    public function forecast(string $granularity = 'week', int $periods = 8, ?int $unitId = null): array
    {
        $opening = $this->exec->cashOnHand($unitId);
        $today = today();

        $buckets = [];

        // ── المستحق حتى اليوم (متأخرات + ما يستحق اليوم) ──
        $buckets[] = $this->bucket('مستحق حتى اليوم', null, $today->copy()->endOfDay(), $unitId);

        // ── فترات للأمام ──
        $cursor = $today->copy()->addDay()->startOfDay();
        for ($i = 1; $i <= $periods; $i++) {
            if ($granularity === 'month') {
                $end = $cursor->copy()->addMonthNoOverflow()->subDay()->endOfDay();
                $label = $cursor->copy()->translatedFormat('F Y');
            } else {
                $end = $cursor->copy()->addDays(6)->endOfDay();
                $label = $cursor->format('d/m').' – '.$end->format('d/m');
            }

            $buckets[] = $this->bucket($label, $cursor->copy(), $end, $unitId);
            $cursor = $end->copy()->addDay()->startOfDay();
        }

        // ── الرصيد التراكمي ──
        $running = $opening;
        foreach ($buckets as $idx => $bucket) {
            $running += $bucket['net'];
            $buckets[$idx]['running_balance'] = round($running, 2);
        }

        return [
            'opening_cash' => round($opening, 2),
            'granularity' => $granularity,
            'buckets' => $buckets,
            'total_inflow' => round(array_sum(array_column($buckets, 'inflow')), 2),
            'total_outflow' => round(array_sum(array_column($buckets, 'outflow')), 2),
            'ending_balance' => round($running, 2),
        ];
    }

    /** @return array{label: string, from: ?string, to: string, inflow: float, outflow: float, net: float} */
    private function bucket(string $label, ?Carbon $from, Carbon $to, ?int $unitId): array
    {
        $inflow = $this->receivables($from, $to, $unitId) + $this->cheques('incoming', $from, $to, $unitId);
        $outflow = $this->payables($from, $to, $unitId) + $this->cheques('outgoing', $from, $to, $unitId);

        return [
            'label' => $label,
            'from' => $from?->toDateString(),
            'to' => $to->toDateString(),
            'inflow' => round($inflow, 2),
            'outflow' => round($outflow, 2),
            'net' => round($inflow - $outflow, 2),
        ];
    }

    private function receivables(?Carbon $from, Carbon $to, ?int $unitId): float
    {
        $query = Invoice::query()
            ->where('type', 'sale')
            ->whereIn('status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid'])
            ->whereRaw('total_amount > paid_amount')
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId));

        $this->whereDueBetween($query, 'COALESCE(due_date, invoice_date)', $from, $to);

        return (float) $query->sum(DB::raw('total_amount - paid_amount'));
    }

    private function payables(?Carbon $from, Carbon $to, ?int $unitId): float
    {
        $query = PurchaseInvoice::query()
            ->whereIn('status', ['confirmed', 'partial_paid'])
            ->whereRaw('total_amount > paid_amount')
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId));

        $this->whereDueBetween($query, 'COALESCE(due_date, invoice_date)', $from, $to);

        return (float) $query->sum(DB::raw('total_amount - paid_amount'));
    }

    private function cheques(string $direction, ?Carbon $from, Carbon $to, ?int $unitId): float
    {
        $statuses = $direction === 'incoming' ? ['pending', 'deposited'] : ['pending'];

        $query = Cheque::query()
            ->where('direction', $direction)
            ->whereIn('status', $statuses)
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId));

        $this->whereDueBetween($query, 'due_date', $from, $to);

        return (float) $query->sum('amount');
    }

    /** فلتر حسب تاريخ الاستحقاق: حتى $to (لو $from فارغ) أو بين $from و $to. */
    private function whereDueBetween(Builder $query, string $column, ?Carbon $from, Carbon $to): void
    {
        if ($from === null) {
            $query->whereRaw("$column <= ?", [$to]);
        } else {
            $query->whereRaw("$column BETWEEN ? AND ?", [$from, $to]);
        }
    }
}
