<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStockTransfer extends EditRecord
{
    protected static string $resource = StockTransferResource::class;

    protected static ?string $title = 'تعديل تحويل المخزون';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف التحويل')
                ->visible(fn (): bool => $this->getRecord()->isDraft()),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ التحويل بنجاح';
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
                ->title('التحويل مؤكد — لا يمكن تعديل البنود')
                ->warning()
                ->send();
        }
    }
}
