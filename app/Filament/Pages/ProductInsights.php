<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\ProductInsightsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class ProductInsights extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 31;

    protected static ?string $title = 'تحليل الأصناف (ABC والطلب)';

    protected static ?string $navigationLabel = 'تحليل الأصناف';

    protected string $view = 'filament.pages.product-insights';

    public string $report_type = 'abc';  // abc | reorder

    public ?string $from_date = null;

    public ?string $to_date = null;

    public int $velocity_days = 30;

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
                ->label('التحليل')
                ->options([
                    'abc' => 'تصنيف ABC',
                    'reorder' => 'اقتراحات إعادة الطلب',
                ])
                ->default('abc')
                ->live(),

            DatePicker::make('from_date')
                ->label('من تاريخ')
                ->visible(fn () => $this->report_type === 'abc')
                ->live()
                ->displayFormat('Y-m-d'),

            DatePicker::make('to_date')
                ->label('إلى تاريخ')
                ->visible(fn () => $this->report_type === 'abc')
                ->live()
                ->displayFormat('Y-m-d'),

            Select::make('velocity_days')
                ->label('حساب السرعة على')
                ->options([
                    30 => 'آخر 30 يوم',
                    60 => 'آخر 60 يوم',
                    90 => 'آخر 90 يوم',
                ])
                ->default(30)
                ->visible(fn () => $this->report_type === 'reorder')
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
            $service = app(ProductInsightsService::class);
            $unit = $this->business_unit_id ?: null;

            return $this->report_type === 'reorder'
                ? $service->reorderSuggestions($this->velocity_days, $unit)
                : $service->abcAnalysis(
                    $this->from_date ?? today()->subDays(90)->toDateString(),
                    $this->to_date ?? today()->toDateString(),
                    $unit,
                );
        })();
    }
}
