<?php

namespace App\Filament\Resources\UnitTransfers\Pages;

use App\Filament\Resources\UnitTransfers\UnitTransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnitTransfers extends ListRecords
{
    protected static string  $resource = UnitTransferResource::class;
    protected static ?string $title    = 'التحويلات الداخلية بين الوحدات';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('تحويل جديد'),
        ];
    }
}
