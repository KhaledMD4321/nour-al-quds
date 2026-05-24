<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseInvoice extends EditRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected static ?string $title = 'تعديل فاتورة المشتريات';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف الفاتورة')
                ->visible(fn (): bool => $this->getRecord()->isDraft()),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ الفاتورة بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        // منع تعديل الفواتير المؤكدة (الحقول الحساسة)
        if ($this->getRecord()->isConfirmed()) {
            Notification::make()
                ->title('الفاتورة مؤكدة — لا يمكن تعديل البنود')
                ->warning()
                ->send();
        }
    }
}
