<?php

namespace App\Filament\Concerns;

use App\Models\Module;

/**
 * يُضاف هذا trait لأي Filament Resource أو Page لتحقق من حالة الوحدة قبل عرضها في القائمة.
 *
 * مثال الاستخدام:
 *   use HasModuleGuard;
 *   protected static string $module = 'sales';
 */
trait HasModuleGuard
{
    public static function shouldRegisterNavigation(): bool
    {
        $code = static::$module ?? null;
        if (! $code) return true;

        return Module::isActive($code);
    }
}
