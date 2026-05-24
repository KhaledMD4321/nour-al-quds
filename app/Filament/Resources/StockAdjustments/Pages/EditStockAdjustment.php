<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStockAdjustment extends EditRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected static ?string $title = 'تعديل تسوية المخزون';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف التسوية')
                ->visible(fn (): bool => $this->getRecord()->isDraft()),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ التسوية بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if ($this->getRecord()->isConfirmed()) {
            Notification::make()
                ->title('التسوية مؤكدة — لا يمكن تعديل البنود')
                ->warning()
                ->send();
        }
    }
}
