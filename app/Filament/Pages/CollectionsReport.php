<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\Reports\CollectionsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class CollectionsReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 28;

    protected static ?string $title = 'مؤشرات التحصيل';

    protected static ?string $navigationLabel = 'مؤشرات التحصيل';

    protected string $view = 'filament.pages.collections-report';

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

        return $user->can('accounting.ledger.view');
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
        ])->columns(3);
    }

    public function getSummary(): object
    {
        return app(CollectionsService::class)->summary(
            $this->from_date ?? today()->subDays(90)->toDateString(),
            $this->to_date ?? today()->toDateString(),
            $this->business_unit_id ?: null,
        );
    }

    public function getBuckets(): object
    {
        return app(CollectionsService::class)->agingBuckets($this->business_unit_id ?: null);
    }

    public function getTrend(): Collection
    {
        return app(CollectionsService::class)->monthlyTrend(6, $this->business_unit_id ?: null);
    }
}
