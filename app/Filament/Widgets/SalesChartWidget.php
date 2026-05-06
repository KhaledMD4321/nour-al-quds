<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class SalesChartWidget extends ChartWidget
{
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';
    protected ?string $heading = 'مبيعات آخر 30 يوم';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $user   = auth()->user();
        $unitId = ($user && !$user->isSuperAdmin() && $user->business_unit_id)
            ? $user->business_unit_id : null;

        $data   = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date     = today()->subDays($i);
            $labels[] = $date->format('d/m');

            $total = Invoice::where('type', 'sale')
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereDate('invoice_date', $date)
                ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
                ->sum('total_amount');

            $data[] = (float) $total;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'المبيعات (ج.م)',
                    'data'            => $data,
                    'backgroundColor' => '#3b82f6',
                    'borderRadius'    => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
