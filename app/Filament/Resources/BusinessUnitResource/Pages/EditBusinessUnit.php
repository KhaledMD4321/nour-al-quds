<?php

namespace App\Filament\Resources\BusinessUnitResource\Pages;

use App\Filament\Resources\BusinessUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusinessUnit extends EditRecord
{
    protected static string $resource = BusinessUnitResource::class;

    protected static ?string $title = 'تعديل الوحدة التشغيلية';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ الوحدة التشغيلية بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
