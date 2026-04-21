<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use App\Filament\Resources\ChartOfAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChartOfAccount extends EditRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected static ?string $title = 'تعديل الحساب';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات الحساب بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
