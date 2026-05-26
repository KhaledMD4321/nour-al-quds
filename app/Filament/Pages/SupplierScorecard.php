<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\SupplierInsightsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class SupplierScorecard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 32;

    protected static ?string $title = 'بطاقة أداء الموردين';

    protected static ?string $navigationLabel = 'بطاقة أداء الموردين';

    protected string $view = 'filament.pages.supplier-scorecard';

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
        $this->from_date = today()->subDays(90)->toDateString();
        $this->to_date = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('from_date')->label('من تاريخ')->live()->displayFormat('Y-m-d'),
            DatePicker::make('to_date')->label('إلى تاريخ')->live()->displayFormat('Y-m-d'),
            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(3);
    }

    public function getDpo(): object
    {
        return app(SupplierInsightsService::class)->dpo(
            $this->from_date ?? today()->subDays(90)->toDateString(),
            $this->to_date ?? today()->toDateString(),
            $this->business_unit_id ?: null,
        );
    }

    public function getScorecard(): Collection
    {
        return app(SupplierInsightsService::class)->scorecard(
            $this->from_date ?? today()->subDays(90)->toDateString(),
            $this->to_date ?? today()->toDateString(),
            $this->business_unit_id ?: null,
        );
    }
}
