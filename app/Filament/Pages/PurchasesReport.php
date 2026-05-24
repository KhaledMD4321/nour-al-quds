<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\ReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class PurchasesReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 24;

    protected static ?string $title = 'تقارير المشتريات';

    protected static ?string $navigationLabel = 'تقارير المشتريات';

    protected string $view = 'filament.pages.purchases-report';

    public string $report_type = 'supplier';  // supplier | product

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

        return $user->can('reports.purchases.view');
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
                    'supplier' => 'بحسب المورد',
                    'product' => 'بحسب الصنف',
                ])
                ->default('supplier')
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
        return app(ReportService::class)->purchasesSummary(
            $this->from_date ?? today()->startOfMonth()->toDateString(),
            $this->to_date ?? today()->toDateString(),
            $this->business_unit_id ?: null
        );
    }

    public function getData(): Collection
    {
        $service = app(ReportService::class);
        $from = $this->from_date ?? today()->startOfMonth()->toDateString();
        $to = $this->to_date ?? today()->toDateString();
        $unit = $this->business_unit_id ?: null;

        return match ($this->report_type) {
            'supplier' => $service->purchasesBySupplier($from, $to, $unit),
            'product' => $service->purchasesByProduct($from, $to, $unit),
            default => collect(),
        };
    }
}
