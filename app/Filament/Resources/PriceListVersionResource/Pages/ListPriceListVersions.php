<?php

namespace App\Filament\Resources\PriceListVersionResource\Pages;

use App\Filament\Pages\ImportPriceListPage;
use App\Filament\Resources\PriceListVersionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPriceListVersions extends ListRecords
{
    protected static string $resource = PriceListVersionResource::class;

    protected static ?string $title = 'قوائم الأسعار';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة قائمة يدوياً'),

            Action::make('import')
                ->label('رفع لستة من Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->url(fn (): string => ImportPriceListPage::getUrl()),
        ];
    }
}
