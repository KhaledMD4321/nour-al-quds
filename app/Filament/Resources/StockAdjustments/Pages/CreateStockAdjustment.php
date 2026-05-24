<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\StockAdjustment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected static ?string $title = 'إنشاء تسوية مخزون جديدة';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference_number'] = StockAdjustment::generateReference();
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء التسوية بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
