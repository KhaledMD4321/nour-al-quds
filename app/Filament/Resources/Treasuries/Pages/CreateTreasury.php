<?php

namespace App\Filament\Resources\Treasuries\Pages;

use App\Filament\Resources\Treasuries\TreasuryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTreasury extends CreateRecord
{
    protected static string  $resource = TreasuryResource::class;
    protected static ?string $title    = 'إنشاء خزينة جديدة';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الخزينة بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
