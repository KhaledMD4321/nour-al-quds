<?php

namespace App\Filament\Widgets;

use App\Modules\Reports\ExecutiveDashboardService;
use Filament\Widgets\Widget;

/**
 * الكارت التنفيذي — 6 مؤشرات صحّة بإشارة مرور (لصاحب الشركة).
 * يظهر أعلى لوحة التحكم ليعطي نظرة فورية على حالة الشركة.
 */
class ExecutiveScorecardWidget extends Widget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.executive-scorecard';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    /** @return array<int, array> */
    public function getKpis(): array
    {
        $user = auth()->user();
        $unitId = ($user && ! $user->isSuperAdmin() && $user->business_unit_id)
            ? $user->business_unit_id
            : null;

        return app(ExecutiveDashboardService::class)->scorecard($unitId);
    }
}
