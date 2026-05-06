<?php

namespace App\Filament\Widgets;

use App\Models\Treasury;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TreasuryStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user   = auth()->user();
        $unitId = ($user && !$user->isSuperAdmin() && $user->business_unit_id)
            ? $user->business_unit_id : null;

        $treasuries = Treasury::where('is_active', true)
            ->when($unitId, fn ($q) => $q->where('business_unit_id', $unitId))
            ->with('businessUnit')
            ->orderBy('business_unit_id')
            ->orderBy('type')
            ->get();

        $stats = [];

        foreach ($treasuries as $t) {
            $icon  = $t->type === 'cash'
                ? 'heroicon-o-banknotes'
                : 'heroicon-o-building-library';

            $balance = (float) $t->current_balance;
            $color   = $balance > 0 ? 'success' : ($balance < 0 ? 'danger' : 'gray');

            $stats[] = Stat::make(
                $t->name,
                number_format($balance, 2) . ' ج.م'
            )
                ->description(
                    ($t->businessUnit?->name ?? '—') . ' — ' .
                    ($t->type === 'cash' ? 'نقدية' : 'بنك')
                )
                ->icon($icon)
                ->color($color);
        }

        // لو مفيش خزائن مرتبطة بالوحدة
        if (empty($stats)) {
            $stats[] = Stat::make('الخزائن', '—')
                ->description('لا توجد خزائن نشطة')
                ->color('gray')
                ->icon('heroicon-o-banknotes');
        }

        return $stats;
    }
}
