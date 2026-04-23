<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'تعديل الصنف';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('أرشفة'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ الصنف بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
