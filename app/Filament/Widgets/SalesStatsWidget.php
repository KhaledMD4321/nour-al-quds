<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\QuickSale;
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

        return [
            Stat::make('مبيعات اليوم', number_format($todaySales, 2).' ج.م')
                ->description($todayCount.' فاتورة')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('مبيعات الشهر', number_format($monthSales, 2).' ج.م')
                ->description(today()->translatedFormat('F Y'))
                ->color('info')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('فواتير مفتوحة', $openInvoices)
                ->description('محتاجة تحصيل')
                ->color($openInvoices > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),
        ];
    }
}
