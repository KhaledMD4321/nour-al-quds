<?php

namespace App\Modules\Notifications;

use App\Models\Cheque;
use App\Models\Invoice;
use App\Modules\Reports\ExecutiveDashboardService;
use App\Modules\Reports\ReportService;
use Illuminate\Support\Facades\DB;

/**
 * DailyDigestService — يبني ملخّص "صباح الخير" لصاحب الشركة.
 * يجمّع أرقام الأمس + الحالة الحالية في حزمة واحدة جاهزة للإرسال.
 */
class DailyDigestService
{
    public function __construct(
        private ExecutiveDashboardService $exec,
        private ReportService $reports,
    ) {}

    /** @return array<string, mixed> */
    public function build(): array
    {
        $yesterday = today()->subDay();

        // ── مبيعات الأمس ──
        $yesterdaySales = $this->exec->sales(
            $yesterday->copy()->startOfDay(),
            $yesterday->copy()->endOfDay()
        );
        $yesterdayCount = Invoice::query()
            ->where('type', 'sale')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereDate('invoice_date', $yesterday)
            ->count();

        // ── النقدية + النمو الشهري ──
        $cash = $this->exec->cashOnHand();
        $growth = $this->exec->salesGrowth();

        // ── فواتير محتاجة تحصيل ──
        $openQuery = Invoice::query()
            ->where('type', 'sale')
            ->whereIn('status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid'])
            ->whereRaw('total_amount > paid_amount');
        $openCount = (clone $openQuery)->count();
        $openTotal = (float) (clone $openQuery)->sum(DB::raw('total_amount - paid_amount'));

        // ── المتأخرات (من أعمار الديون) ──
        $aging = $this->reports->customerAging();
        $overdueAr = (float) $aging->sum(fn ($r) => $r->days_30 + $r->days_60 + $r->days_90 + $r->over_90);

        // ── شيكات تستحق خلال 7 أيام ──
        $cheques = Cheque::dueSoon(7)->get();

        // ── أعلى صنف مبيعاً أمس ──
        $top = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.type', 'sale')
            ->whereNotIn('invoices.status', ['draft', 'cancelled'])
            ->whereDate('invoices.invoice_date', $yesterday)
            ->selectRaw('products.name AS name, SUM(invoice_items.total) AS val')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('val')
            ->first();

        return [
            'date' => $yesterday->toDateString(),
            'yesterday_sales' => $yesterdaySales,
            'yesterday_count' => $yesterdayCount,
            'cash' => $cash,
            'month_growth' => $growth['month'],
            'open_count' => $openCount,
            'open_total' => $openTotal,
            'overdue_ar' => $overdueAr,
            'cheques_count' => $cheques->count(),
            'cheques_in' => (float) $cheques->where('direction', 'incoming')->sum('amount'),
            'cheques_out' => (float) $cheques->where('direction', 'outgoing')->sum('amount'),
            'top_product' => $top->name ?? null,
            'top_product_value' => (float) ($top->val ?? 0),
        ];
    }
}
