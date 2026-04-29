<?php

namespace App\Filament\Resources\Treasuries\Pages;

use App\Filament\Resources\Treasuries\TreasuryResource;
use Filament\Resources\Pages\EditRecord;

class EditTreasury extends EditRecord
{
    protected static string  $resource = TreasuryResource::class;
    protected static ?string $title    = 'تعديل بيانات الخزينة';

    protected function getHeaderActions(): array
    {
        return []; // لا حذف من هنا — softDelete فقط عبر super_admin
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات الخزينة';
    }

    // الرصيد لا يُعدَّل عبر الفورم — يتحرك فقط عبر TreasuryService
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['current_balance']);
        return $data;
    }
}
