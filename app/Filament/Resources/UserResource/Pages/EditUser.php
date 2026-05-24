<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'تعديل المستخدم';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->hidden(fn (): bool => $this->record->id === 1),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات المستخدم بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $role = $this->data['roles'] ?? null;

        if ($role) {
            $this->record->syncRoles([$role]);
        }
    }

    /**
     * Prevent editing super admin (id=1) by anyone except themselves.
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if ($this->record->id === 1 && auth()->id() !== 1) {
            abort(403, 'لا يمكن تعديل حساب الإدارة العُليا');
        }
    }
}
