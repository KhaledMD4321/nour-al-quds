<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Models\SalesTarget;
use App\Modules\Reports\BudgetService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BudgetVsActual extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 34;

    protected static ?string $title = 'الأهداف والأداء';

    protected static ?string $navigationLabel = 'الأهداف والأداء';

    protected string $view = 'filament.pages.budget-vs-actual';

    public int $year;

    public ?int $business_unit_id = null;

    /** @var array<int, float|string> */
    public array $targets = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('reports.pl.view');
    }

    public function mount(): void
    {
        $this->year = (int) today()->year;

        $this->business_unit_id = auth()->user()?->isSuperAdmin()
            ? BusinessUnit::query()->where('is_active', true)->orderBy('id')->value('id')
            : auth()->user()?->business_unit_id;

        $this->loadTargets();
    }

    public function updatedYear(): void
    {
        $this->loadTargets();
    }

    public function updatedBusinessUnitId(): void
    {
        $this->loadTargets();
    }

    public function loadTargets(): void
    {
        $this->targets = app(BudgetService::class)->monthlyTargets($this->year, $this->business_unit_id ?: null);
    }

    public function saveTargets(): void
    {
        if (! $this->business_unit_id) {
            Notification::make()->title('اختر وحدة أولاً')->danger()->send();

            return;
        }

        for ($m = 1; $m <= 12; $m++) {
            SalesTarget::updateOrCreate(
                ['business_unit_id' => $this->business_unit_id, 'year' => $this->year, 'month' => $m],
                ['target_amount' => (float) ($this->targets[$m] ?? 0)],
            );
        }

        Notification::make()->title('تم حفظ الأهداف بنجاح')->success()->send();
    }

    /** @return array<int, object> */
    public function getRows(): array
    {
        return app(BudgetService::class)->comparison($this->year, $this->business_unit_id ?: null, $this->targets);
    }

    /** @return array<int, string> */
    public function getUnitOptions(): array
    {
        return BusinessUnit::query()->where('is_active', true)->pluck('name', 'id')->all();
    }

    /** @return array<int, int> */
    public function getYearOptions(): array
    {
        $current = (int) today()->year;

        return [$current - 1, $current, $current + 1];
    }
}
