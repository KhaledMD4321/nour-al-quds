<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Models\Category;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected static ?string $title = 'تعديل التصنيف';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('أرشفة')
                ->action(function (): void {
                    try {
                        $this->record->delete();
                        Notification::make()
                            ->title('تم أرشفة التصنيف بنجاح')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            RestoreAction::make()->label('استعادة'),

            ForceDeleteAction::make()
                ->label('حذف نهائي')
                ->visible(fn (): bool => auth()->user()->hasRole('super_admin')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات التصنيف بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
