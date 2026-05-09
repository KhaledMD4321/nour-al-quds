<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\CustomFieldRenderer;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'إضافة عميل جديد';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة العميل بنجاح';
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
