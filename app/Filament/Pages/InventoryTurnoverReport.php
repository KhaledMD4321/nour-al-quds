<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\ReportService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class InventoryTurnoverReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 27;

    protected static ?string $title = 'دوران المخزون والركود';

    protected static ?string $navigationLabel = 'دوران المخزون والركود';

    protected string $view = 'filament.pages.inventory-turnover-report';

    public string $report_type = 'turnover';  // turnover | dead

    public ?string $from_date = null;

    public ?string $to_date = null;

    public int $days_threshold = 90;

    public ?int $business_unit_id = null;

    private ?Collection $dataCache = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('reports.inventory.view');
    }

    public function mount(): void
    {
        $this->from_date = today()->subDays(90)->toDateString();
        $this->to_date = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('report_type')
                ->label('التقرير')
                ->options([
                    'turnover' => 'معدل الدوران',
                    'dead' => 'البضاعة الراكدة',
                ])
                ->default('turnover')
                ->live(),

            DatePicker::make('from_date')
                ->label('من تاريخ')
                ->visible(fn () => $this->report_type === 'turnover')
                ->live()
                ->displayFormat('Y-m-d'),

            DatePicker::make('to_date')
                ->label('إلى تاريخ')
                ->visible(fn () => $this->report_type === 'turnover')
                ->live()
                ->displayFormat('Y-m-d'),

            Select::make('days_threshold')
                ->label('بدون حركة منذ')
                ->options([
                    60 => '60 يوم',
                    90 => '90 يوم',
                    180 => '180 يوم',
                ])
                ->default(90)
                ->visible(fn () => $this->report_type === 'dead')
                ->live(),

            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(4);
    }

    public function getData(): Collection
    {
        return $this->dataCache ??= (function () {
            $service = app(ReportService::class);
            $unit = $this->business_unit_id ?: null;

            return $this->report_type === 'dead'
                ? $service->deadStock($this->days_threshold, $unit)
                : $service->inventoryTurnover($this->fromDate(), $this->toDate(), $unit);
        })();
    }

    public function getSummary(): object
    {
        $rows = $this->getData();

        if ($this->report_type === 'dead') {
            return (object) [
                'count' => $rows->count(),
                'value' => round((float) $rows->sum('total_value'), 2),
            ];
        }

        $value = (float) $rows->sum('stock_value');
        $cogs = (float) $rows->sum('cogs');
        $days = max(1, (int) Carbon::parse($this->fromDate())->diffInDays(Carbon::parse($this->toDate())) + 1);

        return (object) [
            'value' => round($value, 2),
            'cogs' => round($cogs, 2),
            'turnover' => $value > 0 ? round(($cogs * 365 / $days) / $value, 2) : 0.0,
        ];
    }

    private function fromDate(): string
    {
        return $this->from_date ?? today()->subDays(90)->toDateString();
    }

    private function toDate(): string
    {
        return $this->to_date ?? today()->toDateString();
    }
}
