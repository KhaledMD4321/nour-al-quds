<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Services\CustomFieldRenderer;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'إضافة صنف جديد';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة الصنف بنجاح';
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
