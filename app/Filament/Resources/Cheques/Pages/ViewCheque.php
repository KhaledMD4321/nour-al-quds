<?php

namespace App\Filament\Resources\Cheques\Pages;

use App\Filament\Resources\Cheques\ChequeResource;
use App\Models\Cheque;
use App\Models\Treasury;
use App\Modules\Finance\ChequeService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCheque extends ViewRecord
{
    protected static string $resource = ChequeResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Cheque $cheque */
        $cheque = $this->getRecord();

        $actions = [];

        // إيداع
        if ($cheque->canDeposit()
            && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.cheque.deposit'))
        ) {
            $actions[] = Action::make('deposit')
                ->label('إيداع بالبنك')
                ->icon('heroicon-o-building-library')
                ->color('info')
                ->modalHeading('إيداع الشيك بالبنك')
                ->form([
                    Select::make('bank_treasury_id')
                        ->label('البنك')
                        ->options(
                            Treasury::active()
                                ->where('type', 'bank')
                                ->where('business_unit_id', $cheque->business_unit_id)
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->default($cheque->treasury_id)
                        ->required(),
                ])
                ->action(function (array $data) use ($cheque) {
                    try {
                        app(ChequeService::class)->deposit($cheque->id, $data['bank_treasury_id'], auth()->id());
                        Notification::make()->success()->title('تم إيداع الشيك بالبنك')->send();
                        $this->refreshRecord();
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('خطأ')->body($e->getMessage())->persistent()->send();
                    }
                });
        }

        // تحصيل
        if ($cheque->canCollect()
            && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.cheque.collect'))
        ) {
            $actions[] = Action::make('collect')
                ->label('تم التحصيل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->modalHeading('تأكيد تحصيل الشيك')
                ->modalDescription("هل تم تحصيل الشيك #{$cheque->cheque_number} بمبلغ ".number_format((float) $cheque->amount, 2).' ج.م؟')
                ->form($cheque->isOutgoing() ? [
                    Select::make('treasury_id')
                        ->label('البنك المُنفِّذ للصرف')
                        ->options(
                            Treasury::active()
                                ->where('type', 'bank')
                                ->where('business_unit_id', $cheque->business_unit_id)
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->default($cheque->treasury_id)
                        ->required(),
                ] : [])
                ->action(function (array $data) use ($cheque) {
                    try {
                        $bankId = $cheque->isOutgoing() ? ($data['treasury_id'] ?? null) : null;
                        app(ChequeService::class)->collect($cheque->id, auth()->id(), $bankId);
                        Notification::make()->success()->title('تم تحصيل الشيك')->send();
                        $this->refreshRecord();
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('خطأ')->body($e->getMessage())->persistent()->send();
                    }
                });
        }

        // رفض
        if ($cheque->canBounce()
            && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.cheque.bounce'))
        ) {
            $actions[] = Action::make('bounce')
                ->label('مرفوض')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->modalHeading('تسجيل رفض الشيك')
                ->form([
                    Textarea::make('bounce_reason')
                        ->label('سبب الرفض')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) use ($cheque) {
                    try {
                        app(ChequeService::class)->bounce($cheque->id, $data['bounce_reason'], auth()->id());
                        Notification::make()->warning()->title('تم تسجيل رفض الشيك')->send();
                        $this->refreshRecord();
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('خطأ')->body($e->getMessage())->persistent()->send();
                    }
                });
        }

        // استبدال
        if ($cheque->canReplace()
            && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.cheque.replace'))
        ) {
            $actions[] = Action::make('replace')
                ->label('استبدال')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->modalHeading('استبدال الشيك المرفوض')
                ->form([
                    TextInput::make('cheque_number')
                        ->label('رقم الشيك الجديد')
                        ->required()
                        ->maxLength(50),

                    TextInput::make('bank_name')
                        ->label('اسم البنك')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('amount')
                        ->label('المبلغ (ج.م)')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->default($cheque->amount),

                    DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق الجديد')
                        ->required()
                        ->displayFormat('Y-m-d'),
                ])
                ->action(function (array $data) use ($cheque) {
                    try {
                        $new = app(ChequeService::class)->replace($cheque->id, $data, auth()->id());
                        Notification::make()->success()
                            ->title('تم استبدال الشيك')
                            ->body("الشيك الجديد: #{$new->cheque_number}")
                            ->send();
                        $this->redirect(ChequeResource::getUrl('view', ['record' => $new]));
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('خطأ')->body($e->getMessage())->persistent()->send();
                    }
                });
        }

        return $actions;
    }
}
