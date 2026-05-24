<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class CustomLogin extends Login
{
    protected string $view = 'filament.pages.auth.custom-login';

    public function getTitle(): string|Htmlable
    {
        return 'تسجيل الدخول — نور القدس';
    }

    public function getHeading(): string|Htmlable|null
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
