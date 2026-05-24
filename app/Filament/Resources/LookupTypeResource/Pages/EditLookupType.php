<?php

namespace App\Filament\Resources\LookupTypeResource\Pages;

use App\Filament\Resources\LookupTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLookupType extends EditRecord
{
    protected static string $resource = LookupTypeResource::class;

    protected static ?string $title = 'تعديل القائمة';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->action(function (): void {
                    if ($this->record->is_system) {
                        Notification::make()
                            ->title('مش ممكن تحذف قائمة نظامية')
                            ->danger()
                            ->send();

                        return;
                    }
                    $this->record->delete();
                    Notification::make()
                        ->title('تم حذف القائمة')
                        ->success()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ القائمة بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
