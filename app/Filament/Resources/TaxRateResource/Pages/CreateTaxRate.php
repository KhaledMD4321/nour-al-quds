<?php

namespace App\Filament\Resources\TaxRateResource\Pages;

use App\Filament\Resources\TaxRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxRate extends CreateRecord
{
    protected static string $resource = TaxRateResource::class;

    protected static ?string $title = 'إضافة ضريبة';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة الضريبة بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
