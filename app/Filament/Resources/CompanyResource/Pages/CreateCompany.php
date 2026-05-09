<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Services\CustomFieldRenderer;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'إضافة مصنّع';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة المصنّع بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        CustomFieldRenderer::saveValues($this->getRecord(), $this->data);
    }
}
