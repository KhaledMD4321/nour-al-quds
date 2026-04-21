<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'بيانات المصنّع';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
        ];
    }
}
