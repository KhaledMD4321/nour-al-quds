<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use App\Models\StockTransfer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    protected static ?string $title = 'إنشاء تحويل مخزون جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference_number'] = StockTransfer::generateReference();
        $data['created_by']       = Auth::id();
        $data['status']           = 'draft';
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء التحويل بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
