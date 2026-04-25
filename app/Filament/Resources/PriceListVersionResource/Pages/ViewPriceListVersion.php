<?php

namespace App\Filament\Resources\PriceListVersionResource\Pages;

use App\Filament\Resources\PriceListVersionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPriceListVersion extends ViewRecord
{
    protected static string $resource = PriceListVersionResource::class;

    protected static ?string $title = 'تفاصيل قائمة الأسعار';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
        ];
    }
}
