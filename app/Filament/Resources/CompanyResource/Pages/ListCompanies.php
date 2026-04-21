<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'المصنّعين';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة مصنّع'),
        ];
    }
}
