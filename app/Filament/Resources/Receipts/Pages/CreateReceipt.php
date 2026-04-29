<?php

namespace App\Filament\Resources\Receipts\Pages;

use App\Filament\Resources\Receipts\ReceiptResource;
use App\Modules\Finance\ReceiptService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReceipt extends CreateRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['created_by']      = auth()->id();
        $data['business_unit_id'] = auth()->user()?->isSuperAdmin()
            ? ($data['business_unit_id'] ?? auth()->user()->business_unit_id)
            : auth()->user()->business_unit_id;

        try {
            return app(ReceiptService::class)->createReceipt($data);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('خطأ في الإيصال')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الإيصال بنجاح';
    }
}
