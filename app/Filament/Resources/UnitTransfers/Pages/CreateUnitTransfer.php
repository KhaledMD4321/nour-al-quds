<?php

namespace App\Filament\Resources\UnitTransfers\Pages;

use App\Filament\Resources\UnitTransfers\UnitTransferResource;
use App\Models\UnitTransfer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUnitTransfer extends CreateRecord
{
    protected static string $resource = UnitTransferResource::class;

    protected static ?string $title = 'إنشاء تحويل داخلي جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';
        $data['total_amount'] = 0;

        if (empty($data['reference_number'])) {
            $data['reference_number'] = UnitTransfer::generateReference();
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء وثيقة التحويل بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
