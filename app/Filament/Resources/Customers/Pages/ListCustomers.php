<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'العملاء';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة عميل'),
        ];
    }
}
