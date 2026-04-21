<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use App\Filament\Resources\ChartOfAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChartOfAccount extends CreateRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected static ?string $title = 'إضافة حساب';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة الحساب بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
