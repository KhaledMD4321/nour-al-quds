<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\CashFlowForecastService;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class CashFlowForecast extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 26;

    protected static ?string $title = 'توقّع التدفق النقدي';

    protected static ?string $navigationLabel = 'توقّع التدفق النقدي';

    protected string $view = 'filament.pages.cash-flow-forecast';

    public string $granularity = 'week';  // week | month

    public ?int $business_unit_id = null;

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
        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('granularity')
                ->label('التقسيم')
                ->options([
                    'week' => 'أسبوعي (8 أسابيع)',
                    'month' => 'شهري (3 أشهر)',
                ])
                ->default('week')
                ->live(),

            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(2);
    }

    /** @return array<string, mixed> */
    public function getForecast(): array
    {
        $periods = $this->granularity === 'month' ? 3 : 8;

        return app(CashFlowForecastService::class)->forecast(
            $this->granularity,
            $periods,
            $this->business_unit_id ?: null,
        );
    }
}
