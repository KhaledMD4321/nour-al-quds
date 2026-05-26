<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\ReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class GrossMarginReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 22;

    protected static ?string $title = 'هامش الربح';

    protected static ?string $navigationLabel = 'هامش الربح';

    protected string $view = 'filament.pages.gross-margin-report';

    public string $report_type = 'manufacturer';  // manufacturer | product

    public ?string $from_date = null;

    public ?string $to_date = null;

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
        $this->from_date = today()->startOfMonth()->toDateString();
        $this->to_date = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('report_type')
                ->label('تجميع بحسب')
                ->options([
                    'manufacturer' => 'بحسب المصنّع',
                    'product' => 'بحسب الصنف',
                ])
                ->default('manufacturer')
                ->live(),

            DatePicker::make('from_date')
                ->label('من تاريخ')
                ->live()
                ->displayFormat('Y-m-d'),

            DatePicker::make('to_date')
                ->label('إلى تاريخ')
                ->live()
                ->displayFormat('Y-m-d'),

            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(4);
    }

    public function getSummary(): object
    {
        $rows = app(ReportService::class)->grossMarginByProduct(
            $this->from_date ?? today()->startOfMonth()->toDateString(),
            $this->to_date ?? today()->toDateString(),
            $this->business_unit_id ?: null,
        );

        $revenue = (float) $rows->sum('revenue');
        $cogs = (float) $rows->sum('cogs');
        $profit = round($revenue - $cogs, 2);

        return (object) [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $profit,
            'margin_pct' => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0.0,
        ];
    }

    public function getData(): Collection
    {
        $service = app(ReportService::class);
        $from = $this->from_date ?? today()->startOfMonth()->toDateString();
        $to = $this->to_date ?? today()->toDateString();
        $unit = $this->business_unit_id ?: null;

        return $this->report_type === 'product'
            ? $service->grossMarginByProduct($from, $to, $unit)
            : $service->grossMarginByManufacturer($from, $to, $unit);
    }
}
