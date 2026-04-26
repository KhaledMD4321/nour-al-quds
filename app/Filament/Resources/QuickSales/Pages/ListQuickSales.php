<?php

namespace App\Filament\Resources\QuickSales\Pages;

use App\Filament\Resources\QuickSales\QuickSaleResource;
use Filament\Resources\Pages\ListRecords;

class ListQuickSales extends ListRecords
{
    protected static string  $resource = QuickSaleResource::class;
    protected static ?string $title    = 'سجل المبيعات السريعة';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
