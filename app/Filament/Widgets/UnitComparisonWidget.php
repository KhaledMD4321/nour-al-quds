<?php

namespace App\Filament\Widgets;

use App\Modules\Reports\ExecutiveDashboardService;
use Filament\Widgets\Widget;

/**
 * مقارنة الوحدتين (المعرض مقابل التوزيع) لهذا الشهر — لصاحب الشركة.
 */
class UnitComparisonWidget extends Widget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.unit-comparison';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    /** @return array<int, array> */
    public function getUnits(): array
    {
        return app(ExecutiveDashboardService::class)->unitComparison();
    }
}
