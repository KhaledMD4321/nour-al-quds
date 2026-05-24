<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListTreasuryTransactions extends ListRecords
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected static ?string $title = 'حركات الخزائن';

    protected function getHeaderActions(): array
    {
        return []; // قراءة فقط — لا إنشاء يدوي
    }
}
