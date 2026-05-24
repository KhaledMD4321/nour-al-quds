<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Modules\Sales\ReturnService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected static ?string $title = 'تعديل مرتجع مشتريات';

    protected function getHeaderActions(): array
    {
        /** @var PurchaseReturn $return */
        $return = $this->getRecord();

        return [
            Action::make('confirm')
                ->label('تأكيد المرتجع')
                ->icon('heroicon-o-check-circle')
                ->color('danger')
                ->visible(fn () => $return->isDraft())
                ->requiresConfirmation()
                ->modalHeading('تأكيد مرتجع المشتريات')
                ->modalDescription('سيتم خصم الكميات من المخزون. هل أنت متأكد؟')
                ->action(function () use ($return) {
                    try {
                        app(ReturnService::class)->confirmPurchaseReturn($return);
                        Notification::make()
                            ->title('تم تأكيد المرتجع بنجاح')
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

    // لا يُعدَّل المرتجع بعد التأكيد
    public function beforeSave(): void
    {
        if ($this->getRecord()->isConfirmed()) {
            Notification::make()
                ->title('لا يمكن تعديل مرتجع مؤكد')
                ->danger()
                ->send();
            $this->halt();
        }
    }
}
