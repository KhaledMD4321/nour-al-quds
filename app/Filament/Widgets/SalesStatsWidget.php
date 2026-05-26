<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\QuickSale;
use App\Modules\Reports\ExecutiveDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = auth()->user();
        $unitId = ($user && ! $user->isSuperAdmin() && $user->business_unit_id)
            ? $user->business_unit_id : null;

        // مبيعات اليوم (فواتير + بيع سريع)
        $todayInvoices = Invoice::where('type', 'sale')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereDate('invoice_date', today())
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        $todayQuick = QuickSale::whereDate('created_at', today())
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        $todaySales = (float) $todayInvoices + (float) $todayQuick;

        // عدد فواتير اليوم
        $todayCount = Invoice::where('type', 'sale')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereDate('invoice_date', today())
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->count();

        // مبيعات الشهر
        $monthInvoices = Invoice::where('type', 'sale')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereMonth('invoice_date', today()->month)
            ->whereYear('invoice_date', today()->year)
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        $monthQuick = QuickSale::whereMonth('created_at', today()->month)
            ->whereYear('created_at', today()->year)
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->sum('total_amount');

        $monthSales = (float) $monthInvoices + (float) $monthQuick;

        // فواتير مفتوحة (محتاجة تحصيل)
        $openInvoices = Invoice::where('type', 'sale')
            ->whereIn('status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid'])
            ->whereRaw('total_amount > paid_amount')
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->count();

        $growth = app(ExecutiveDashboardService::class)->salesGrowth($unitId);
        [$monthDesc, $monthColor, $monthIcon] = $this->growthLabel($growth['month'], 'الشهر الماضي');
        [$yearDesc, $yearColor, $yearIcon] = $this->growthLabel($growth['year'], 'العام الماضي');

        return [
            Stat::make('مبيعات اليوم', number_format($todaySales, 2).' ج.م')
                ->description($todayCount.' فاتورة')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('مبيعات الشهر', number_format($monthSales, 2).' ج.م')
                ->description($monthDesc)
                ->descriptionIcon($monthIcon)
                ->color($monthColor)
                ->icon('heroicon-o-chart-bar'),

            Stat::make('مبيعات السنة', number_format($growth['year']['current'], 2).' ج.م')
                ->description($yearDesc)
                ->descriptionIcon($yearIcon)
                ->color($yearColor)
                ->icon('heroicon-o-calendar'),

            Stat::make('فواتير مفتوحة', $openInvoices)
                ->description('محتاجة تحصيل')
                ->color($openInvoices > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),
        ];
    }

    /**
     * صياغة فرق النمو (سهم + نسبة + لون).
     *
     * @param  array{current: float, previous: float, delta: float, up: bool}  $g
     * @return array{0: string, 1: string, 2: string} [description, color, icon]
     */
    private function growthLabel(array $g, string $period): array
    {
        if ($g['previous'] <= 0 && $g['current'] <= 0) {
            return ['لا بيانات للمقارنة', 'gray', 'heroicon-m-minus'];
        }

        $pct = number_format(abs($g['delta']), 1);

        return $g['up']
            ? ["▲ {$pct}% عن {$period}", 'success', 'heroicon-m-arrow-trending-up']
            : ["▼ {$pct}% عن {$period}", 'danger', 'heroicon-m-arrow-trending-down'];
    }
}
