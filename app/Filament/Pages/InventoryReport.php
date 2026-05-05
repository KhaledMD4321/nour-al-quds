<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Models\Product;
use App\Models\Warehouse;
use App\Modules\Reports\ReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class InventoryReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-archive-box';
    protected static string|\UnitEnum|null   $navigationGroup = 'التقارير';
    protected static ?int                    $navigationSort  = 22;
    protected static ?string                 $title           = 'تقارير المخزون';
    protected static ?string                 $navigationLabel = 'تقارير المخزون';
    protected string                         $view            = 'filament.pages.inventory-report';

    public string  $report_type      = 'balance';   // balance | slow | movement
    public ?int    $warehouse_id     = null;
    public ?int    $business_unit_id = null;
    public ?int    $product_id       = null;
    public ?string $from_date        = null;
    public ?string $to_date          = null;
    public int     $slow_days        = 90;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('reports.inventory.view');
    }

    public function mount(): void
    {
        $this->from_date = today()->startOfMonth()->toDateString();
        $this->to_date   = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('report_type')
                ->label('نوع التقرير')
                ->options([
                    'balance'  => 'أرصدة المخزون الحالية',
                    'slow'     => 'الأصناف الراكدة',
                    'movement' => 'حركة صنف',
                ])
                ->default('balance')
                ->live(),

            Select::make('warehouse_id')
                ->label('المخزن')
                ->options(fn () => ['' => 'كل المخازن'] + Warehouse::pluck('name', 'id')->toArray())
                ->live(),

            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),

            Select::make('product_id')
                ->label('الصنف')
                ->options(fn () => Product::where('is_active', true)
                    ->orderBy('code')
                    ->get()
                    ->mapWithKeys(fn ($p) => [$p->id => $p->code . ' — ' . $p->name])
                    ->toArray()
                )
                ->searchable()
                ->visible(fn () => $this->report_type === 'movement')
                ->live(),

            DatePicker::make('from_date')
                ->label('من تاريخ')
                ->visible(fn () => $this->report_type === 'movement')
                ->live()
                ->displayFormat('Y-m-d'),

            DatePicker::make('to_date')
                ->label('إلى تاريخ')
                ->visible(fn () => $this->report_type === 'movement')
                ->live()
                ->displayFormat('Y-m-d'),

            Select::make('slow_days')
                ->label('فترة الركود')
                ->options([
                    30  => '30 يوم',
                    60  => '60 يوم',
                    90  => '90 يوم',
                    180 => '6 أشهر',
                    365 => 'سنة',
                ])
                ->default(90)
                ->visible(fn () => $this->report_type === 'slow')
                ->live(),
        ])->columns(3);
    }

    public function getData(): \Illuminate\Support\Collection
    {
        $service = app(ReportService::class);

        return match($this->report_type) {
            'balance'  => $service->stockBalance($this->warehouse_id ?: null, $this->business_unit_id ?: null),
            'slow'     => $service->slowMovingStock($this->slow_days, $this->warehouse_id ?: null),
            'movement' => $this->product_id
                ? $service->productMovement($this->product_id, $this->from_date ?? today()->startOfMonth()->toDateString(), $this->to_date ?? today()->toDateString(), $this->warehouse_id ?: null)
                : collect(),
            default    => collect(),
        };
    }
}
