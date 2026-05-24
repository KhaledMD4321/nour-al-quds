<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\ReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class ProfitLossReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 21;

    protected static ?string $title = 'الأرباح والخسائر';

    protected static ?string $navigationLabel = 'الأرباح والخسائر';

    protected string $view = 'filament.pages.profit-loss-report';

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
        $this->from_date = today()->startOfYear()->toDateString();
        $this->to_date = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
                ->options(fn () => ['' => 'كل الوحدات (موحّد)'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(3);
    }

    public function getReport(): object
    {
        return app(ReportService::class)->profitLoss(
            $this->business_unit_id ?: null,
            $this->from_date ?? today()->startOfYear()->toDateString(),
            $this->to_date ?? today()->toDateString()
        );
    }

    public function getConsolidated(): ?object
    {
        // عرض الوحدتين بشكل منفصل فقط للسوبر أدمن بدون فلتر وحدة
        if (! auth()->user()?->isSuperAdmin() || $this->business_unit_id) {
            return null;
        }

        return app(ReportService::class)->profitLossConsolidated(
            $this->from_date ?? today()->startOfYear()->toDateString(),
            $this->to_date ?? today()->toDateString()
        );
    }
}
