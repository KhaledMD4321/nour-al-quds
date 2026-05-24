<?php

namespace App\Filament\Resources\CompanySettingResource\Pages;

use App\Filament\Resources\CompanySettingResource;
use App\Models\CompanySetting;
use Filament\Resources\Pages\EditRecord;

class EditCompanySetting extends EditRecord
{
    protected static string $resource = CompanySettingResource::class;

    protected static ?string $title = 'إعدادات الشركة';

    /**
     * Singleton page — always load the one and only company settings row.
     * We accept an optional record key so Filament doesn't require an ID in the URL.
     */
    public function mount(int|string|null $record = null): void
    {
        $this->record = CompanySetting::getInstance();

        $this->authorizeAccess();

        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ إعدادات الشركة بنجاح';
    }
}
