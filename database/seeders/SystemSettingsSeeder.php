<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // ─── بيانات الشركة ────────────────────────────────────────────────
            ['group' => 'company', 'key' => 'name',           'value' => 'شركة نور القدس للأدوات الصحية والسباكة', 'label' => 'اسم الشركة',          'type' => 'text',     'sort_order' => 1],
            ['group' => 'company', 'key' => 'address',        'value' => '',                                         'label' => 'العنوان',             'type' => 'textarea', 'sort_order' => 2],
            ['group' => 'company', 'key' => 'phone',          'value' => '',                                         'label' => 'التليفون',            'type' => 'text',     'sort_order' => 3],
            ['group' => 'company', 'key' => 'phone2',         'value' => '',                                         'label' => 'تليفون 2',            'type' => 'text',     'sort_order' => 4],
            ['group' => 'company', 'key' => 'email',          'value' => '',                                         'label' => 'البريد الإلكتروني',   'type' => 'text',     'sort_order' => 5],
            ['group' => 'company', 'key' => 'tax_number',     'value' => '',                                         'label' => 'البطاقة الضريبية',    'type' => 'text',     'sort_order' => 6],
            ['group' => 'company', 'key' => 'commercial_reg', 'value' => '',                                         'label' => 'السجل التجاري',       'type' => 'text',     'sort_order' => 7],
            ['group' => 'company', 'key' => 'logo',           'value' => '',                                         'label' => 'لوجو الشركة',         'type' => 'file',     'sort_order' => 8],

            // ─── إعدادات الفاتورة ─────────────────────────────────────────────
            ['group' => 'invoice', 'key' => 'terms',                'value' => '',   'label' => 'شروط البيع',                   'type' => 'textarea', 'sort_order' => 1],
            ['group' => 'invoice', 'key' => 'return_policy',        'value' => '',   'label' => 'سياسة المرتجع',                'type' => 'textarea', 'sort_order' => 2],
            ['group' => 'invoice', 'key' => 'warranty',             'value' => '',   'label' => 'الضمان',                        'type' => 'text',     'sort_order' => 3],
            ['group' => 'invoice', 'key' => 'footer_note',          'value' => '',   'label' => 'ملاحظة ثابتة أسفل الفاتورة',  'type' => 'textarea', 'sort_order' => 4],
            ['group' => 'invoice', 'key' => 'show_discount',        'value' => '1',  'label' => 'إظهار الخصم في الفاتورة',      'type' => 'toggle',   'sort_order' => 5],
            ['group' => 'invoice', 'key' => 'show_signature',       'value' => '1',  'label' => 'إظهار مكان التوقيع',           'type' => 'toggle',   'sort_order' => 6],
            ['group' => 'invoice', 'key' => 'default_payment_days', 'value' => '30', 'label' => 'مدة السداد الافتراضية (يوم)',  'type' => 'number',   'sort_order' => 7],

            // ─── تسلسل الأرقام ────────────────────────────────────────────────
            ['group' => 'numbering', 'key' => 'invoice_prefix',   'value' => 'INV-', 'label' => 'بادئة الفاتورة',      'type' => 'text',   'sort_order' => 1],
            ['group' => 'numbering', 'key' => 'receipt_prefix',   'value' => 'REC-', 'label' => 'بادئة سند القبض',     'type' => 'text',   'sort_order' => 2],
            ['group' => 'numbering', 'key' => 'payment_prefix',   'value' => 'PAY-', 'label' => 'بادئة سند الصرف',     'type' => 'text',   'sort_order' => 3],
            ['group' => 'numbering', 'key' => 'quotation_prefix', 'value' => 'QUO-', 'label' => 'بادئة عرض السعر',     'type' => 'text',   'sort_order' => 4],
            ['group' => 'numbering', 'key' => 'cheque_prefix',    'value' => 'CHQ-', 'label' => 'بادئة الشيك',         'type' => 'text',   'sort_order' => 5],
            ['group' => 'numbering', 'key' => 'digits',           'value' => '5',    'label' => 'عدد الخانات',         'type' => 'number', 'sort_order' => 6],
            ['group' => 'numbering', 'key' => 'yearly_reset',     'value' => '0',    'label' => 'إعادة تعيين سنوي',   'type' => 'toggle', 'sort_order' => 7],

            // ─── الافتراضيات ──────────────────────────────────────────────────
            ['group' => 'defaults', 'key' => 'default_payment_method', 'value' => 'cash', 'label' => 'طريقة الدفع الافتراضية',       'type' => 'select', 'sort_order' => 1,
                'options' => json_encode([['value' => 'cash', 'label' => 'نقدي'], ['value' => 'credit', 'label' => 'آجل']])],
            ['group' => 'defaults', 'key' => 'default_credit_limit',   'value' => '0',    'label' => 'حد الائتمان الافتراضي (ج.م)', 'type' => 'number', 'sort_order' => 2],
            ['group' => 'defaults', 'key' => 'default_min_stock',      'value' => '5',    'label' => 'حد المخزون الأدنى الافتراضي', 'type' => 'number', 'sort_order' => 3],

            // ─── التنبيهات ────────────────────────────────────────────────────
            ['group' => 'alerts', 'key' => 'low_stock_enabled',    'value' => '1',  'label' => 'تنبيه المخزون المنخفض',           'type' => 'toggle', 'sort_order' => 1],
            ['group' => 'alerts', 'key' => 'low_stock_threshold',  'value' => '5',  'label' => 'حد التنبيه (كمية)',               'type' => 'number', 'sort_order' => 2],
            ['group' => 'alerts', 'key' => 'cheque_due_enabled',   'value' => '1',  'label' => 'تنبيه الشيكات المستحقة',          'type' => 'toggle', 'sort_order' => 3],
            ['group' => 'alerts', 'key' => 'cheque_due_days',      'value' => '7',  'label' => 'قبل كام يوم',                     'type' => 'number', 'sort_order' => 4],
            ['group' => 'alerts', 'key' => 'overdue_invoice_days', 'value' => '30', 'label' => 'الفاتورة المتأخرة (بعد كام يوم)', 'type' => 'number', 'sort_order' => 5],

            // ─── إعدادات الطباعة ──────────────────────────────────────────────
            ['group' => 'print', 'key' => 'header_color',   'value' => '#1e40af', 'label' => 'لون الترويسة',           'type' => 'color',    'sort_order' => 1],
            ['group' => 'print', 'key' => 'show_logo',      'value' => '1',       'label' => 'إظهار اللوجو',           'type' => 'toggle',   'sort_order' => 2],
            ['group' => 'print', 'key' => 'logo_size',      'value' => 'medium',  'label' => 'حجم اللوجو',             'type' => 'select',   'sort_order' => 3,
                'options' => json_encode([['value' => 'small', 'label' => 'صغير'], ['value' => 'medium', 'label' => 'متوسط'], ['value' => 'large', 'label' => 'كبير']])],
            ['group' => 'print', 'key' => 'receipt_footer', 'value' => '',        'label' => 'تذييل سند القبض',        'type' => 'textarea', 'sort_order' => 4],
            ['group' => 'print', 'key' => 'copies',         'value' => '1',       'label' => 'عدد نسخ الطباعة',        'type' => 'number',   'sort_order' => 5],

            // ─── قواعد الأعمال ────────────────────────────────────────────────
            ['group' => 'business_rules', 'key' => 'allow_negative_stock',     'value' => '0',      'label' => 'السماح بالبيع بدون مخزون كافي',      'type' => 'toggle', 'sort_order' => 1],
            ['group' => 'business_rules', 'key' => 'allow_over_credit_limit',  'value' => '0',      'label' => 'السماح بتجاوز حد الائتمان',           'type' => 'toggle', 'sort_order' => 2],
            ['group' => 'business_rules', 'key' => 'max_discount_percent',     'value' => '40',     'label' => 'الحد الأقصى للخصم (%)',                'type' => 'number', 'sort_order' => 3],
            ['group' => 'business_rules', 'key' => 'require_invoice_approval', 'value' => '0',      'label' => 'الفاتورة تحتاج موافقة مدير',          'type' => 'toggle', 'sort_order' => 4],
            ['group' => 'business_rules', 'key' => 'allow_edit_confirmed',     'value' => '0',      'label' => 'السماح بتعديل الفاتورة المؤكدة',      'type' => 'toggle', 'sort_order' => 5],
            ['group' => 'business_rules', 'key' => 'negative_treasury_action', 'value' => 'reject', 'label' => 'عند محاولة سحب أكتر من الرصيد',      'type' => 'select', 'sort_order' => 6,
                'options' => json_encode([['value' => 'reject', 'label' => 'رفض'], ['value' => 'warn', 'label' => 'تحذير فقط']])],
        ];

        foreach ($settings as $row) {
            SystemSetting::updateOrCreate(
                ['group' => $row['group'], 'key' => $row['key']],
                $row
            );
        }
    }
}
