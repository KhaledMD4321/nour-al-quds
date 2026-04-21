<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected static ?string $title = 'بيانات التصنيف';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
        ];
    }
}
