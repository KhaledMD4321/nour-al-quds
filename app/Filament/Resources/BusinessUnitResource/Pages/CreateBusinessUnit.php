<?php

namespace App\Filament\Resources\BusinessUnitResource\Pages;

use App\Filament\Resources\BusinessUnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessUnit extends CreateRecord
{
    protected static string $resource = BusinessUnitResource::class;

    protected static ?string $title = 'إضافة وحدة تشغيلية';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة الوحدة التشغيلية بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
