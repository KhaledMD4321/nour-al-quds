<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseReturns extends ListRecords
{
    protected static string $resource = PurchaseReturnResource::class;

    protected static ?string $title = 'مرتجعات المشتريات';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('مرتجع جديد'),
        ];
    }
}
