<?php

namespace App\Filament\Resources\UnitTransfers\Pages;

use App\Filament\Resources\UnitTransfers\UnitTransferResource;
use App\Models\UnitTransfer;
use App\Modules\Sales\UnitTransferService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditUnitTransfer extends EditRecord
{
    protected static string  $resource = UnitTransferResource::class;
    protected static ?string $title    = 'تعديل التحويل الداخلي';

    protected function getHeaderActions(): array
    {
        /** @var UnitTransfer $transfer */
        $transfer = $this->getRecord();

        return [
            Action::make('confirm')
                ->label('تأكيد التحويل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $transfer->isDraft())
                ->requiresConfirmation()
                ->modalHeading('تأكيد التحويل الداخلي')
                ->modalDescription('سيتم توليد فاتورة بيع وفاتورة شراء داخليتين وتحديث المخزون. هل أنت متأكد؟')
                ->action(function () use ($transfer) {
                    try {
                        app(UnitTransferService::class)->confirmTransfer($transfer);
                        Notification::make()
                            ->title('تم تأكيد التحويل بنجاح')
                            ->success()
                            ->send();
                        $this->refreshFormData(['status', 'total_amount']);
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('خطأ في التأكيد')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    // لا يُعدَّل التحويل بعد تأكيده
    public function beforeSave(): void
    {
        if ($this->getRecord()->isConfirmed()) {
            Notification::make()
                ->title('لا يمكن تعديل تحويل مؤكد')
                ->danger()
                ->send();
            $this->halt();
        }
    }
}
