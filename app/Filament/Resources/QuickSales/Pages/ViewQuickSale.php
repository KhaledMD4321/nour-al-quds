<?php

namespace App\Filament\Resources\QuickSales\Pages;

use App\Filament\Resources\QuickSales\QuickSaleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewQuickSale extends ViewRecord
{
    protected static string  $resource = QuickSaleResource::class;
    protected static ?string $title    = 'تفاصيل الإيصال';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receipt')
                ->label('طباعة الإيصال')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('quick-sale.receipt', $this->getRecord()->id))
                ->openUrlInNewTab(),
        ];
    }
}
