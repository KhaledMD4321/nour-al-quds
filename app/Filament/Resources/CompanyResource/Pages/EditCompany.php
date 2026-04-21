<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'تعديل بيانات المصنّع';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('أرشفة'),
            RestoreAction::make()->label('استعادة'),
            ForceDeleteAction::make()
                ->label('حذف نهائي')
                ->visible(fn () => auth()->user()->hasRole('super_admin')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات المصنّع بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
