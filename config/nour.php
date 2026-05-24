<?php

/**
 * Nour Al-Quds ERP — Central Business Configuration
 *
 * This file is the SINGLE SOURCE OF TRUTH for all business enums and
 * settings. Every module must reference values from here instead of
 * hardcoding strings in models, services, or Filament resources.
 *
 * Usage:
 *   config('nour.units_of_measure')
 *   \App\Helpers\NourConfig::units()
 */

return [

    // ─── Units of Measure ──────────────────────────────────────────────────────
    // Used on products.unit_of_measure

    'units_of_measure' => [
        'piece' => 'قطعة',
        'meter' => 'متر',
        'box' => 'علبة',
        'set' => 'طقم',
        'carton' => 'كرتونة',
        'roll' => 'رول',
        'kg' => 'كيلو',
        'liter' => 'لتر',
    ],

    // ─── Currencies ────────────────────────────────────────────────────────────
    // Only EGP for now; add more when needed

    'currencies' => [
        'EGP' => [
            'name' => 'جنيه مصري',
            'symbol' => 'ج.م.',
            'decimal_places' => 2,
        ],
    ],

    'default_currency' => 'EGP',

    // ─── Payment Methods ───────────────────────────────────────────────────────
    // Used on invoices.payment_type, receipts.payment_method, payments.payment_method

    'payment_methods' => [
        'cash' => 'نقدي',
        'cheque' => 'شيك',
        'bank_transfer' => 'تحويل بنكي',
    ],

    // ─── Customer Types ────────────────────────────────────────────────────────

    'customer_types' => [
        'individual' => 'فرد',
        'company' => 'شركة',
        'trader' => 'تاجر',
    ],

    // ─── Invoice Statuses ──────────────────────────────────────────────────────

    'invoice_statuses' => [
        'draft' => 'مسودة',
        'confirmed' => 'مؤكدة',
        'returned' => 'مرتجعة',
        'cancelled' => 'ملغاة',
    ],

    // ─── Cheque Statuses ───────────────────────────────────────────────────────

    'cheque_statuses' => [
        'pending' => 'قيد الانتظار',
        'deposited' => 'مودع بالبنك',
        'collected' => 'تم التحصيل',
        'bounced' => 'مرتجع',
        'replaced' => 'مستبدل',
    ],

    // ─── Stock Adjustment Reasons ──────────────────────────────────────────────

    'adjustment_reasons' => [
        'damaged' => 'تالف',
        'missing' => 'عجز',
        'surplus' => 'زيادة',
        'expired' => 'منتهي الصلاحية',
        'other' => 'أخرى',
    ],

    // ─── Expense Categories ────────────────────────────────────────────────────
    // Used on payments.category

    'expense_categories' => [
        'supplier_payment' => 'دفع مورد',
        'rent' => 'إيجار',
        'salary' => 'رواتب وأجور',
        'transport' => 'نقل ومواصلات',
        'electricity' => 'كهرباء ومياه',
        'other' => 'أخرى',
    ],

];
