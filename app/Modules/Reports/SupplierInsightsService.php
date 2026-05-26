<?php

namespace App\Modules\Reports;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SupplierInsightsService — بطاقة أداء الموردين.
 *   - DPO (متوسط فترة السداد)
 *   - مشتريات / مسدّد / مستحق / مرتجعات ونسبة المرتجع لكل مورد
 */
class SupplierInsightsService
{
    private const OPEN_PURCHASE_STATUSES = ['confirmed', 'partial_paid'];

    public function __construct(private ReportService $reports) {}

    /** إجمالي المستحق للموردين (فواتير شراء مفتوحة). */
    public function openPayables(?int $unitId = null): float
    {
        return (float) PurchaseInvoice::query()
            ->whereIn('status', self::OPEN_PURCHASE_STATUSES)
            ->whereRaw('total_amount > paid_amount')
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->sum(DB::raw('total_amount - paid_amount'));
    }

    /** صافي المشتريات خلال فترة (مشتريات − مرتجعات). */
    public function netPurchases(Carbon $from, Carbon $to, ?int $unitId = null): float
    {
        $purchases = (float) PurchaseInvoice::query()
            ->whereIn('status', ['confirmed', 'paid'])
            ->whereBetween('invoice_date', [$from, $to])
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        $returns = (float) PurchaseReturn::query()
            ->where('status', 'confirmed')
            ->whereBetween('return_date', [$from, $to])
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        return round($purchases - $returns, 2);
    }

    /** @return object{ap: float, purchases: float, days: int, dpo: int} */
    public function dpo(string $fromDate, string $toDate, ?int $unitId = null): object
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();
        $days = max(1, (int) $from->diffInDays($to) + 1);

        $ap = $this->openPayables($unitId);
        $purchases = $this->netPurchases($from, $to, $unitId);

        return (object) [
            'ap' => round($ap, 2),
            'purchases' => $purchases,
            'days' => $days,
            'dpo' => $purchases > 0 ? (int) round($ap / ($purchases / $days)) : 0,
        ];
    }

    /** بطاقة أداء لكل مورد خلال فترة. */
    public function scorecard(string $fromDate, string $toDate, ?int $unitId = null): Collection
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $purchases = $this->reports->purchasesBySupplier($fromDate, $toDate, $unitId);

        $returns = PurchaseReturn::query()
            ->where('purchase_returns.status', 'confirmed')
            ->whereBetween('purchase_returns.return_date', [$from, $to])
            ->when($unitId, fn (Builder $q) => $q->where('purchase_returns.business_unit_id', $unitId))
            ->join('suppliers', 'suppliers.id', '=', 'purchase_returns.supplier_id')
            ->groupBy('suppliers.name')
            ->selectRaw('suppliers.name as name, SUM(purchase_returns.total_amount) as returned')
            ->pluck('returned', 'name');

        return $purchases->map(function ($r) use ($returns) {
            $returned = (float) ($returns[$r->supplier_name] ?? 0);
            $total = (float) $r->total;

            return (object) [
                'supplier' => $r->supplier_name,
                'invoices' => $r->invoice_count,
                'purchases' => $total,
                'paid' => (float) $r->paid,
                'outstanding' => (float) $r->outstanding,
                'returns' => round($returned, 2),
                'return_rate' => $total > 0 ? round($returned / $total * 100, 1) : 0.0,
            ];
        });
    }
}
