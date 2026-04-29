<?php

namespace App\Filament\Resources\Treasuries\Pages;

use App\Filament\Resources\Treasuries\TreasuryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTreasuries extends ListRecords
{
    protected static string  $resource = TreasuryResource::class;
    protected static ?string $title    = 'الخزائن';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('خزينة جديدة')
                ->visible(fn () => auth()->user()?->can('finance.treasury.create')),
        ];
    }
}
