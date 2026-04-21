<?php

namespace App\Filament\Resources\BusinessUnitResource\Pages;

use App\Filament\Resources\BusinessUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessUnits extends ListRecords
{
    protected static string $resource = BusinessUnitResource::class;

    protected static ?string $title = 'الوحدات التشغيلية';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة وحدة'),
        ];
    }
}
