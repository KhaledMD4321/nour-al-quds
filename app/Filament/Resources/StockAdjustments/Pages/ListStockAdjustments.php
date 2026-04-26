<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected static ?string $title = 'تسويات المخزون';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إنشاء تسوية'),
        ];
    }
}
