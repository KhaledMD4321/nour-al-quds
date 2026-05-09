<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\CustomFieldRenderer;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'تعديل بيانات العميل';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('حذف'),
            ForceDeleteAction::make()->label('حذف نهائي'),
            RestoreAction::make()->label('استعادة'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات العميل بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['custom_fields'] = CustomFieldRenderer::loadValues($this->getRecord());
        return $data;
    }

    protected function afterSave(): void
    {
        CustomFieldRenderer::saveValues($this->getRecord(), $this->data);
    }
}
