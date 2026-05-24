<?php

namespace App\Services;

class QuickAccessRegistry
{
    /**
     * كل الاختصارات المتاحة في النظام.
     * الـ URLs تطابق routes الفعلية للمشروع.
     */
    public static function all(): array
    {
        return [
            // ─── المبيعات ───────────────────────────────────────────────────────
            'new_invoice' => [
                'label' => 'فاتورة جديدة',
                'icon' => '🧾',
                'url' => '/admin/new-invoice',
                'color' => '#059669',
                'bg' => '#dcfce7',
                'group' => 'المبيعات',
                'permission' => 'sales.invoice.create',
            ],
            'quick_sale' => [
                'label' => 'بيع سريع',
                'icon' => '⚡',
                'url' => '/admin/quick-sale-page',
                'color' => '#d97706',
                'bg' => '#fef3c7',
                'group' => 'المبيعات',
                'permission' => 'sales.quick.create',
            ],
            'new_quotation' => [
                'label' => 'عرض سعر',
                'icon' => '📋',
                'url' => '/admin/new-quotation',
                'color' => '#7c3aed',
                'bg' => '#ede9fe',
                'group' => 'المبيعات',
                'permission' => 'sales.invoice.create',
            ],
            'invoices_list' => [
                'label' => 'فواتير المبيعات',
                'icon' => '📑',
                'url' => '/admin/invoices',
                'color' => '#059669',
                'bg' => '#f0fdf4',
                'group' => 'المبيعات',
                'permission' => 'sales.invoice.create',
            ],

            // ─── الخزينة ────────────────────────────────────────────────────────
            'new_receipt' => [
                'label' => 'سند قبض',
                'icon' => '💰',
                'url' => '/admin/receipts/create',
                'color' => '#2563eb',
                'bg' => '#dbeafe',
                'group' => 'الخزينة',
                'permission' => 'finance.receipt.create',
            ],
            'new_payment' => [
                'label' => 'سند صرف',
                'icon' => '💸',
                'url' => '/admin/payments/create',
                'color' => '#dc2626',
                'bg' => '#fef2f2',
                'group' => 'الخزينة',
                'permission' => 'finance.payment.create',
            ],
            'cheques' => [
                'label' => 'الشيكات',
                'icon' => '📝',
                'url' => '/admin/cheques',
                'color' => '#b45309',
                'bg' => '#fef3c7',
                'group' => 'الخزينة',
                'permission' => 'finance.cheque.view',
            ],
            'treasuries' => [
                'label' => 'الخزائن',
                'icon' => '🏦',
                'url' => '/admin/treasuries',
                'color' => '#1e40af',
                'bg' => '#eff6ff',
                'group' => 'الخزينة',
                'permission' => 'finance.treasury.view',
            ],

            // ─── المشتريات ──────────────────────────────────────────────────────
            'new_purchase' => [
                'label' => 'فاتورة شراء',
                'icon' => '🛒',
                'url' => '/admin/purchase-invoices/create',
                'color' => '#0891b2',
                'bg' => '#cffafe',
                'group' => 'المشتريات',
                'permission' => 'purchases.create',
            ],

            // ─── المخزون ────────────────────────────────────────────────────────
            'stock' => [
                'label' => 'أرصدة المخزون',
                'icon' => '📦',
                'url' => '/admin/stocks',
                'color' => '#6d28d9',
                'bg' => '#ede9fe',
                'group' => 'المخزون',
                'permission' => 'inventory.view',
            ],
            'stock_transfer' => [
                'label' => 'تحويل مخزون',
                'icon' => '🔄',
                'url' => '/admin/stock-transfers',
                'color' => '#6d28d9',
                'bg' => '#f5f3ff',
                'group' => 'المخزون',
                'permission' => 'inventory.transfer',
            ],

            // ─── التقارير ───────────────────────────────────────────────────────
            'customer_statement' => [
                'label' => 'كشف حساب عميل',
                'icon' => '📊',
                'url' => '/admin/customer-statement',
                'color' => '#4b5563',
                'bg' => '#f3f4f6',
                'group' => 'التقارير',
                'permission' => 'accounting.ledger.view',
            ],
            'supplier_statement' => [
                'label' => 'كشف حساب مورد',
                'icon' => '📈',
                'url' => '/admin/supplier-statement',
                'color' => '#4b5563',
                'bg' => '#f9fafb',
                'group' => 'التقارير',
                'permission' => 'accounting.ledger.view',
            ],
            'trial_balance' => [
                'label' => 'ميزان المراجعة',
                'icon' => '⚖️',
                'url' => '/admin/trial-balance',
                'color' => '#1e40af',
                'bg' => '#dbeafe',
                'group' => 'التقارير',
                'permission' => 'accounting.trial_balance.view',
            ],
            'aging_report' => [
                'label' => 'أعمار الديون',
                'icon' => '⏰',
                'url' => '/admin/aging-report',
                'color' => '#dc2626',
                'bg' => '#fef2f2',
                'group' => 'التقارير',
                'permission' => 'reports.sales',
            ],
            'profit_loss' => [
                'label' => 'الأرباح والخسائر',
                'icon' => '📉',
                'url' => '/admin/profit-loss-report',
                'color' => '#059669',
                'bg' => '#dcfce7',
                'group' => 'التقارير',
                'permission' => 'reports.profit_loss',
            ],

            // ─── البيانات ───────────────────────────────────────────────────────
            'customers' => [
                'label' => 'العملاء',
                'icon' => '👥',
                'url' => '/admin/customers',
                'color' => '#0891b2',
                'bg' => '#ecfeff',
                'group' => 'البيانات',
                'permission' => 'contacts.view',
            ],
            'products' => [
                'label' => 'الأصناف',
                'icon' => '🔧',
                'url' => '/admin/products',
                'color' => '#7c3aed',
                'bg' => '#faf5ff',
                'group' => 'البيانات',
                'permission' => 'catalog.view',
            ],
        ];
    }

    /**
     * الاختصارات المسموح بيها للمستخدم (بعد فلتر الصلاحيات)
     */
    public static function forUser($user): array
    {
        return collect(self::all())
            ->filter(function ($action) use ($user) {
                if (! isset($action['permission'])) {
                    return true;
                }

                return $user->isSuperAdmin() || $user->can($action['permission']);
            })
            ->toArray();
    }

    /**
     * الاختصارات المفعّلة للمستخدم (حسب تفضيلاته)
     * لو null → أول 8 متاحين
     */
    public static function activeForUser($user): array
    {
        $available = self::forUser($user);
        $selected = $user->quick_access; // array أو null

        if ($selected === null) {
            return array_slice($available, 0, 8, true);
        }

        return collect($available)
            ->filter(fn ($action, $key) => in_array($key, $selected))
            ->toArray();
    }
}
