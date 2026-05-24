<?php

namespace App\Helpers;

/**
 * NourConfig — Central accessor for all business enums and settings
 * defined in config/nour.php.
 *
 * Usage:
 *   NourConfig::units()                  → ['piece' => 'قطعة', ...]
 *   NourConfig::currencySymbol()         → 'ج.م.'
 *   NourConfig::formatMoney(1234.56)     → '1,234.56 ج.م.'
 */
class NourConfig
{
    // ─── Units of Measure ──────────────────────────────────────────────────────

    /** @return array<string, string>  ['piece' => 'قطعة', ...] */
    public static function units(): array
    {
        return config('nour.units_of_measure', []);
    }

    /** Arabic label for a single unit key. */
    public static function unitLabel(string $key): string
    {
        return static::units()[$key] ?? $key;
    }

    // ─── Currencies ────────────────────────────────────────────────────────────

    /** @return array<string, array{name: string, symbol: string, decimal_places: int}> */
    public static function currencies(): array
    {
        return config('nour.currencies', []);
    }

    /** Default currency code, e.g. 'EGP'. */
    public static function defaultCurrency(): string
    {
        return config('nour.default_currency', 'EGP');
    }

    /**
     * Symbol for the given currency code (or the default currency if null).
     * e.g. NourConfig::currencySymbol()      → 'ج.م.'
     *      NourConfig::currencySymbol('EGP') → 'ج.م.'
     */
    public static function currencySymbol(?string $code = null): string
    {
        $code ??= static::defaultCurrency();

        return static::currencies()[$code]['symbol'] ?? $code;
    }

    // ─── Payment Methods ───────────────────────────────────────────────────────

    /** @return array<string, string>  ['cash' => 'نقدي', ...] */
    public static function paymentMethods(): array
    {
        return config('nour.payment_methods', []);
    }

    // ─── Customer Types ────────────────────────────────────────────────────────

    /** @return array<string, string>  ['individual' => 'فرد', ...] */
    public static function customerTypes(): array
    {
        return config('nour.customer_types', []);
    }

    // ─── Invoice Statuses ──────────────────────────────────────────────────────

    /** @return array<string, string>  ['draft' => 'مسودة', ...] */
    public static function invoiceStatuses(): array
    {
        return config('nour.invoice_statuses', []);
    }

    // ─── Cheque Statuses ───────────────────────────────────────────────────────

    /** @return array<string, string> */
    public static function chequeStatuses(): array
    {
        return config('nour.cheque_statuses', []);
    }

    // ─── Adjustment Reasons ────────────────────────────────────────────────────

    /** @return array<string, string> */
    public static function adjustmentReasons(): array
    {
        return config('nour.adjustment_reasons', []);
    }

    // ─── Expense Categories ────────────────────────────────────────────────────

    /** @return array<string, string> */
    public static function expenseCategories(): array
    {
        return config('nour.expense_categories', []);
    }

    // ─── Money Formatting ──────────────────────────────────────────────────────

    /**
     * Format a monetary amount with the currency symbol.
     *
     * NourConfig::formatMoney(1234.5)        → '1,234.50 ج.م.'
     * NourConfig::formatMoney(1234.5, 'EGP') → '1,234.50 ج.م.'
     */
    public static function formatMoney(float $amount, ?string $currency = null): string
    {
        $currency ??= static::defaultCurrency();
        $decimals = static::currencies()[$currency]['decimal_places'] ?? 2;
        $symbol = static::currencySymbol($currency);
        $formatted = number_format($amount, $decimals);

        return "{$formatted} {$symbol}";
    }
}
