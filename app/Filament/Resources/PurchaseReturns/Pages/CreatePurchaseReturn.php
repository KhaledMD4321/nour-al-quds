<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePurchaseReturn extends CreateRecord
{
    protected static string  $resource = PurchaseReturnResource::class;
    protected static ?string $title    = 'إنشاء مرتجع مشتريات جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by']   = Auth::id();
        $data['status']       = 'draft';
        $data['total_amount'] = 0;

        // توليد رقم المرتجع لو لم يُولَّد بعد
        if (empty($data['reference_number'])) {
            $data['reference_number'] = PurchaseReturn::generateReference();
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء المرتجع بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
