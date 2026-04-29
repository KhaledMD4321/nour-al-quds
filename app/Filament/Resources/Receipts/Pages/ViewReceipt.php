<?php

namespace App\Filament\Resources\Receipts\Pages;

use App\Filament\Resources\Receipts\ReceiptResource;
use App\Models\Receipt;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewReceipt extends ViewRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة الإيصال')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('receipts.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => auth()->user()?->can('print_receipt')),
        ];
    }
}
