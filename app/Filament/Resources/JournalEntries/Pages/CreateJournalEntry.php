<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Modules\Accounting\AccountingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['created_by'] = auth()->id();

        try {
            return app(AccountingService::class)->createManualEntry($data, auth()->id());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            Notification::make()
                ->title('خطأ في حفظ القيد')
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
        return 'تم إنشاء القيد بنجاح — أضف السطور الآن';
    }
}
