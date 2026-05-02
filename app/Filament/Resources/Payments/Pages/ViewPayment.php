<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة السند')
                ->icon('heroicon-o-printer')
                ->color('danger')
                ->url(fn () => route('payments.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.payment.print')),
        ];
    }
}
