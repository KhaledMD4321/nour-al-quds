<?php

namespace App\Filament\Resources\LookupTypeResource\Pages;

use App\Filament\Resources\LookupTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLookupType extends CreateRecord
{
    protected static string $resource = LookupTypeResource::class;

    protected static ?string $title = 'إضافة قائمة جديدة';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة القائمة بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
