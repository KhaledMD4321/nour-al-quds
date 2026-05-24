<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'فواتير المبيعات';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('فاتورة جديدة'),
        ];
    }
}
