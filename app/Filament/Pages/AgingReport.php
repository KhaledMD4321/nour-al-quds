<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\ReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class AgingReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-clock';
    protected static string|\UnitEnum|null   $navigationGroup = 'التقارير';
    protected static ?int                    $navigationSort  = 20;
    protected static ?string                 $title           = 'أعمار الديون';
    protected static ?string                 $navigationLabel = 'أعمار الديون';
    protected string                         $view            = 'filament.pages.aging-report';

    public ?string $as_of_date      = null;
    public ?int    $business_unit_id = null;
    public string  $report_type     = 'customers';  // customers | suppliers

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('reports.aging.view');
    }

    public function mount(): void
    {
        $this->as_of_date = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('report_type')
                ->label('النوع')
                ->options([
                    'customers' => 'ديون العملاء',
                    'suppliers' => 'ديون الموردين',
                ])
                ->default('customers')
                ->live(),

            DatePicker::make('as_of_date')
                ->label('حتى تاريخ')
                ->live()
                ->displayFormat('Y-m-d'),

            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(3);
    }

    public function getData(): \Illuminate\Support\Collection
    {
        $service = app(ReportService::class);

        if ($this->report_type === 'suppliers') {
            return $service->supplierAging($this->business_unit_id ?: null, $this->as_of_date);
        }

        return $service->customerAging($this->business_unit_id ?: null, $this->as_of_date);
    }
}
