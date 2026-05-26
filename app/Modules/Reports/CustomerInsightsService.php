<?php

namespace App\Modules\Reports;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CustomerInsightsService — تحليل العملاء.
 *   - أعلى العملاء (باريتو 80/20) مع النسبة التراكمية
 *   - العملاء غير النشطين (توقّفوا عن الشراء) لإعادة استهدافهم
 */
class CustomerInsightsService
{
    public function __construct(private ReportService $reports) {}

    /** أعلى العملاء مبيعاً مع الحصّة والنسبة التراكمية (باريتو). */
    public function topCustomers(string $fromDate, string $toDate, ?int $unitId = null): Collection
    {
        $rows = $this->reports->salesByCustomer($fromDate, $toDate, $unitId); // مرتّبة تنازلياً بالإجمالي
        $grand = (float) $rows->sum('total');
        $cumulative = 0.0;

        return $rows->map(function ($r) use (&$cumulative, $grand) {
            $cumulative += (float) $r->total;

            return (object) [
                'customer_name' => $r->customer_name,
                'invoice_count' => $r->invoice_count,
                'total' => (float) $r->total,
                'share_pct' => $grand > 0 ? round($r->total / $grand * 100, 1) : 0.0,
                'cumulative_pct' => $grand > 0 ? round($cumulative / $grand * 100, 1) : 0.0,
            ];
        });
    }

    /** العملاء الذين آخر شراء لهم أقدم من المدة المحددة (غير نشطين). */
    public function inactiveCustomers(int $days = 60, ?int $unitId = null): Collection
    {
        $cutoff = today()->subDays($days)->toDateString();

        return Invoice::query()
            ->where('invoices.type', 'sale')
            ->whereNotIn('invoices.status', ['draft', 'cancelled'])
            ->when($unitId, fn (Builder $q) => $q->where('invoices.business_unit_id', $unitId))
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->groupBy('invoices.customer_id', 'customers.code', 'customers.name')
            ->havingRaw('MAX(invoices.invoice_date) < ?', [$cutoff])
            ->selectRaw('customers.code as customer_code, customers.name as customer_name, MAX(invoices.invoice_date) as last_sale, COUNT(*) as orders, SUM(invoices.total_amount) as lifetime')
            ->orderByDesc(DB::raw('SUM(invoices.total_amount)'))
            ->get()
            ->map(fn ($r) => (object) [
                'customer_code' => $r->customer_code,
                'customer_name' => $r->customer_name,
                'last_sale' => Carbon::parse($r->last_sale)->toDateString(),
                'days_since' => (int) Carbon::parse($r->last_sale)->diffInDays(today()),
                'orders' => (int) $r->orders,
                'lifetime' => (float) $r->lifetime,
            ]);
    }
}
