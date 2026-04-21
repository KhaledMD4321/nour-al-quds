<?php

namespace App\Filament\Resources\TaxRateResource\Pages;

use App\Filament\Resources\TaxRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxRate extends EditRecord
{
    protected static string $resource = TaxRateResource::class;

    protected static ?string $title = 'تعديل الضريبة';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات الضريبة بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
