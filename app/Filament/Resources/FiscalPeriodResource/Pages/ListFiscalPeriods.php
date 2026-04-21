<?php

namespace App\Filament\Resources\FiscalPeriodResource\Pages;

use App\Filament\Resources\FiscalPeriodResource;
use Filament\Resources\Pages\ListRecords;

class ListFiscalPeriods extends ListRecords
{
    protected static string $resource = FiscalPeriodResource::class;

    protected static ?string $title = 'الفترات المالية';

    protected function getHeaderActions(): array
    {
        return [];   // no Create button — canCreate() returns false
    }
}
