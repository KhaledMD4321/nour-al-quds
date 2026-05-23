<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;

class CustomLogin extends Login
{
    protected string $view = 'filament.pages.auth.custom-login';

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'تسجيل الدخول — نور القدس';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return null; // نعرض الـ heading داخل الـ blade بشكل مخصص
    }

    /**
     * إخفاء الـ brandLogo التلقائي من أعلى بطاقة Login
     * (يُعرض اللوجو مرة واحدة فقط داخل الـ blade المخصص)
     */
    public function hasLogo(): bool
    {
        return false;
    }
}
