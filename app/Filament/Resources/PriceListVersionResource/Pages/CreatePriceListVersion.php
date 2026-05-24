<?php

namespace App\Filament\Resources\PriceListVersionResource\Pages;

use App\Filament\Resources\PriceListVersionResource;
use App\Models\PriceListVersion;
use Filament\Resources\Pages\CreateRecord;

class CreatePriceListVersion extends CreateRecord
{
    protected static string $resource = PriceListVersionResource::class;

    protected static ?string $title = 'إضافة قائمة أسعار يدوياً';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // حساب رقم الإصدار التالي لهذا المصنّع
        $data['version_number'] = (PriceListVersion::where('company_id', $data['company_id'])
            ->max('version_number') ?? 0) + 1;

        $data['created_by'] = auth()->id();

        // أرشفة الإصدارات النشطة السابقة لنفس المصنّع
        PriceListVersion::where('company_id', $data['company_id'])
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء قائمة الأسعار بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
