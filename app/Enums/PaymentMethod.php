<?php

namespace App\Enums;

/**
 * طرق الدفع المستخدمة في سندات القبض والصرف.
 *
 * مصدر واحد لتسميات طرق الدفع العربية — يحل محل الـ match()
 * المكرر في المتحكمات والصفحات والـ Resources.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Cheque = 'cheque';
    case BankTransfer = 'bank_transfer';

    /** التسمية العربية لطريقة الدفع */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'كاش',
            self::Cheque => 'شيك',
            self::BankTransfer => 'تحويل بنكي',
        };
    }

    /**
     * تسمية آمنة انطلاقاً من قيمة نصية.
     * ترجع القيمة الأصلية لو غير معروفة، و"—" لو فارغة.
     */
    public static function labelFor(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::tryFrom($value)?->label() ?? $value;
    }

    /** خيارات الـ Select بصيغة [value => label] */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $method) => [$method->value => $method->label()])
            ->all();
    }
}
