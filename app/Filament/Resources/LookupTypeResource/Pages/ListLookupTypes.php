<?php

namespace App\Filament\Resources\LookupTypeResource\Pages;

use App\Filament\Resources\LookupTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLookupTypes extends ListRecords
{
    protected static string $resource = LookupTypeResource::class;

    protected static ?string $title = 'القوائم الديناميكية';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة قائمة جديدة'),
        ];
    }
}
