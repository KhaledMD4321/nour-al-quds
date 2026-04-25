<?php

namespace App\Filament\Resources\PriceListVersionResource\Pages;

use App\Filament\Resources\PriceListVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPriceListVersion extends EditRecord
{
    protected static string $resource = PriceListVersionResource::class;

    protected static ?string $title = 'تعديل قائمة الأسعار';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ قائمة الأسعار بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
