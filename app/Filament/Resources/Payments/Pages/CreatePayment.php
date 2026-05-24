<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Treasury;
use App\Modules\Finance\PaymentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['created_by'] = auth()->id();

        // حدد الوحدة بالأولوية:
        // 1. من الفورم مباشرة (super_admin يختار)
        // 2. من الخزينة المختارة
        // 3. من بيانات المستخدم الحالي
        $unitId = (int) ($data['business_unit_id'] ?? 0);

        if ($unitId === 0 && ! empty($data['treasury_id'])) {
            $unitId = (int) Treasury::find($data['treasury_id'])?->business_unit_id;
        }

        if ($unitId === 0) {
            $unitId = (int) auth()->user()?->business_unit_id;
        }

        $data['business_unit_id'] = $unitId;

        try {
            return app(PaymentService::class)->create($data, auth()->id());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            Notification::make()
                ->title('خطأ في حفظ سند الصرف')
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
        return 'تم إنشاء سند الصرف بنجاح';
    }
}
