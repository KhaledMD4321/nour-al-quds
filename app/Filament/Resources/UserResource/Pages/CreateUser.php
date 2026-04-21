<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'إضافة مستخدم';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة المستخدم بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $role = $this->data['roles'] ?? null;

        if ($role) {
            $this->record->syncRoles([$role]);
        }
    }
}
