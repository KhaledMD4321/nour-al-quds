<?php

namespace App\Filament\Resources\Cheques\Pages;

use App\Filament\Resources\Cheques\ChequeResource;
use App\Modules\Finance\ChequeService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCheque extends CreateRecord
{
    protected static string $resource = ChequeResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['created_by'] = auth()->id();

        // fallback للوحدة
        $unitId = (int) ($data['business_unit_id'] ?? 0);
        if ($unitId === 0) {
            $unitId = (int) auth()->user()?->business_unit_id;
        }
        $data['business_unit_id'] = $unitId;

        try {
            return app(ChequeService::class)->register($data, auth()->id());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            Notification::make()
                ->title('خطأ في تسجيل الشيك')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
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
        return 'تم تسجيل الشيك بنجاح';
    }
}
