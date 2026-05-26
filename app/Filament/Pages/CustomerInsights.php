<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\CustomerInsightsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class CustomerInsights extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 29;

    protected static ?string $title = 'تحليل العملاء';

    protected static ?string $navigationLabel = 'تحليل العملاء';

    protected string $view = 'filament.pages.customer-insights';

    public string $report_type = 'top';  // top | inactive

    public ?string $from_date = null;

    public ?string $to_date = null;

    public int $inactive_days = 60;

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

        return $user->can('reports.sales.view');
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
                ->label('التحليل')
                ->options([
                    'top' => 'أعلى العملاء (باريتو)',
                    'inactive' => 'عملاء متوقفون',
                ])
                ->default('top')
                ->live(),

            DatePicker::make('from_date')
                ->label('من تاريخ')
                ->visible(fn () => $this->report_type === 'top')
                ->live()
                ->displayFormat('Y-m-d'),

            DatePicker::make('to_date')
                ->label('إلى تاريخ')
                ->visible(fn () => $this->report_type === 'top')
                ->live()
                ->displayFormat('Y-m-d'),

            Select::make('inactive_days')
                ->label('بدون شراء منذ')
                ->options([
                    60 => '60 يوم',
                    90 => '90 يوم',
                    180 => '180 يوم',
                ])
                ->default(60)
                ->visible(fn () => $this->report_type === 'inactive')
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
            $service = app(CustomerInsightsService::class);
            $unit = $this->business_unit_id ?: null;

            return $this->report_type === 'inactive'
                ? $service->inactiveCustomers($this->inactive_days, $unit)
                : $service->topCustomers(
                    $this->from_date ?? today()->startOfMonth()->toDateString(),
                    $this->to_date ?? today()->toDateString(),
                    $unit,
                );
        })();
    }
}
