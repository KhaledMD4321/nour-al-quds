<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\QuotationFunnelService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class QuotationFunnel extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'تحويل عروض الأسعار';

    protected static ?string $navigationLabel = 'تحويل عروض الأسعار';

    protected string $view = 'filament.pages.quotation-funnel';

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
            DatePicker::make('from_date')->label('من تاريخ')->live()->displayFormat('Y-m-d'),
            DatePicker::make('to_date')->label('إلى تاريخ')->live()->displayFormat('Y-m-d'),
            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(3);
    }

    public function getSummary(): object
    {
        return app(QuotationFunnelService::class)->summary(
            $this->from_date ?? today()->startOfMonth()->toDateString(),
            $this->to_date ?? today()->toDateString(),
            $this->business_unit_id ?: null,
        );
    }

    public function getOpenQuotations(): Collection
    {
        return app(QuotationFunnelService::class)->openQuotations(
            $this->from_date ?? today()->startOfMonth()->toDateString(),
            $this->to_date ?? today()->toDateString(),
            $this->business_unit_id ?: null,
        );
    }
}
