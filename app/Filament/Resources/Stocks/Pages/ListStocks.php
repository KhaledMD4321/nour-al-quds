<?php

namespace App\Filament\Resources\Stocks\Pages;

use App\Filament\Resources\Stocks\StockResource;
use Filament\Resources\Pages\ListRecords;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected static ?string $title = 'جرد المخزون';

    /** ★ لا أزرار في الأعلى — شاشة قراءة فقط */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
