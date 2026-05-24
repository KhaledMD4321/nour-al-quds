<?php

namespace App\Filament\Pages;

use App\Models\Treasury;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TreasurySummary extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|\UnitEnum|null $navigationGroup = 'الخزينة والمالية';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'النظرة الشاملة للخزائن';

    protected static ?string $navigationLabel = 'النظرة الشاملة';

    protected string $view = 'filament.pages.treasury-summary';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTreasuries(): Collection
    {
        return Treasury::with('businessUnit')
            ->active()
            ->orderBy('business_unit_id')
            ->orderBy('type')
            ->get();
    }

    public function getTotalsByUnit(): array
    {
        return Treasury::active()
            ->selectRaw('business_unit_id, SUM(current_balance) as total')
            ->groupBy('business_unit_id')
            ->with('businessUnit')
            ->get()
            ->mapWithKeys(fn ($t) => [$t->businessUnit->name => (float) $t->total])
            ->toArray();
    }

    public function getGrandTotal(): float
    {
        return (float) Treasury::active()->sum('current_balance');
    }
}
