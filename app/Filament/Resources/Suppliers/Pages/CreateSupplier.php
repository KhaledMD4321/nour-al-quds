<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use App\Services\CustomFieldRenderer;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected static ?string $title = 'إضافة مورد جديد';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة المورد بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function afterCreate(): void
    {
        CustomFieldRenderer::saveValues($this->getRecord(), $this->data);
    }
}
