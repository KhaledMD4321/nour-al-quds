<?php

namespace Database\Seeders;

use App\Models\LookupType;
use App\Models\LookupValue;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $lookups = [

            // ── 1. وحدات القياس ── products ────────────────────────────────────
            [
                'code' => 'unit_of_measure',
                'name' => 'وحدة القياس',
                'description' => 'وحدات قياس الأصناف',
                'is_system' => true,
                'values' => [
                    ['code' => 'piece',  'label' => 'قطعة',   'sort_order' => 1, 'is_default' => true],
                    ['code' => 'meter',  'label' => 'متر',    'sort_order' => 2],
                    ['code' => 'box',    'label' => 'علبة',   'sort_order' => 3],
                    ['code' => 'set',    'label' => 'طقم',    'sort_order' => 4],
                    ['code' => 'carton', 'label' => 'كرتونة', 'sort_order' => 5],
                    ['code' => 'kg',     'label' => 'كيلو',   'sort_order' => 6],
                    ['code' => 'roll',   'label' => 'لفة',    'sort_order' => 7],
                    ['code' => 'liter',  'label' => 'لتر',    'sort_order' => 8],
                    ['code' => 'pair',   'label' => 'زوج',    'sort_order' => 9],
                ],
            ],

            // ── 2. نوع العميل ── customers ─────────────────────────────────────
            [
                'code' => 'customer_type',
                'name' => 'نوع العميل',
                'description' => 'تصنيف العملاء',
                'is_system' => true,
                'values' => [
                    ['code' => 'individual', 'label' => 'فرد',           'sort_order' => 1, 'is_default' => true],
                    ['code' => 'company',    'label' => 'شركة',          'sort_order' => 2],
                    ['code' => 'trader',     'label' => 'تاجر',          'sort_order' => 3],
                    ['code' => 'contractor', 'label' => 'مقاول',         'sort_order' => 4],
                    ['code' => 'government', 'label' => 'جهة حكومية',   'sort_order' => 5],
                ],
            ],

            // ── 3. طريقة الدفع ── receipts, payments ──────────────────────────
            [
                'code' => 'payment_method',
                'name' => 'طريقة الدفع',
                'description' => 'وسائل الدفع المتاحة',
                'is_system' => true,
                'values' => [
                    ['code' => 'cash',         'label' => 'كاش',         'sort_order' => 1, 'is_default' => true],
                    ['code' => 'cheque',        'label' => 'شيك',        'sort_order' => 2],
                    ['code' => 'bank_transfer', 'label' => 'تحويل بنكي', 'sort_order' => 3],
                    ['code' => 'instapay',      'label' => 'إنستاباي',   'sort_order' => 4],
                    ['code' => 'vodafone_cash', 'label' => 'فودافون كاش', 'sort_order' => 5],
                ],
            ],

            // ── 4. نوع دفع الفاتورة ── invoices, quick_sales ──────────────────
            [
                'code' => 'invoice_payment_type',
                'name' => 'نوع دفع الفاتورة',
                'description' => 'طريقة سداد الفاتورة',
                'is_system' => true,
                'values' => [
                    ['code' => 'cash',   'label' => 'نقدي',  'sort_order' => 1, 'is_default' => true],
                    ['code' => 'credit', 'label' => 'آجل',   'sort_order' => 2],
                    ['code' => 'cheque', 'label' => 'شيك',   'sort_order' => 3],
                    ['code' => 'mixed',  'label' => 'مختلط', 'sort_order' => 4],
                ],
            ],

            // ── 5. تصنيف المصروفات ── payments ────────────────────────────────
            [
                'code' => 'expense_category',
                'name' => 'تصنيف المصروفات',
                'description' => 'أنواع المصروفات في سندات الصرف',
                'is_system' => false,
                'values' => [
                    ['code' => 'supplier_payment', 'label' => 'دفعة مورد',        'sort_order' => 1, 'is_default' => true],
                    ['code' => 'rent',             'label' => 'إيجار',             'sort_order' => 2],
                    ['code' => 'salary',           'label' => 'مرتبات',           'sort_order' => 3],
                    ['code' => 'transport',        'label' => 'نقل وشحن',         'sort_order' => 4],
                    ['code' => 'electricity',      'label' => 'كهرباء',           'sort_order' => 5],
                    ['code' => 'water',            'label' => 'مياه',             'sort_order' => 6],
                    ['code' => 'phone',            'label' => 'تليفون وإنترنت',   'sort_order' => 7],
                    ['code' => 'maintenance',      'label' => 'صيانة',            'sort_order' => 8],
                    ['code' => 'office_supplies',  'label' => 'مستلزمات مكتب',   'sort_order' => 9],
                    ['code' => 'taxes',            'label' => 'ضرائب ورسوم',     'sort_order' => 10],
                    ['code' => 'other',            'label' => 'أخرى',             'sort_order' => 99],
                ],
            ],

            // ── 6. مصاريف إضافية ── landed_costs ─────────────────────────────
            [
                'code' => 'landed_cost_type',
                'name' => 'نوع المصاريف الإضافية',
                'description' => 'أنواع المصاريف المضافة على فاتورة المشتريات',
                'is_system' => false,
                'values' => [
                    ['code' => 'transport', 'label' => 'نقل وشحن',     'sort_order' => 1, 'is_default' => true],
                    ['code' => 'loading',   'label' => 'تحميل وتنزيل', 'sort_order' => 2],
                    ['code' => 'customs',   'label' => 'جمارك',         'sort_order' => 3],
                    ['code' => 'insurance', 'label' => 'تأمين',         'sort_order' => 4],
                    ['code' => 'other',     'label' => 'أخرى',          'sort_order' => 5],
                ],
            ],

            // ── 7. نوع الخزينة ── treasuries ──────────────────────────────────
            [
                'code' => 'treasury_type',
                'name' => 'نوع الخزينة',
                'description' => 'تصنيف الخزائن',
                'is_system' => true,
                'values' => [
                    ['code' => 'cash', 'label' => 'خزينة نقدية', 'sort_order' => 1, 'is_default' => true],
                    ['code' => 'bank', 'label' => 'حساب بنكي',   'sort_order' => 2],
                ],
            ],

            // ── 8. سبب تسوية المخزون ── stock_adjustment_items ────────────────
            [
                'code' => 'adjustment_reason',
                'name' => 'سبب تسوية المخزون',
                'description' => 'أسباب فروقات الجرد',
                'is_system' => false,
                'values' => [
                    ['code' => 'damaged',  'label' => 'تالف',             'sort_order' => 1],
                    ['code' => 'shortage', 'label' => 'عجز',              'sort_order' => 2, 'is_default' => true],
                    ['code' => 'surplus',  'label' => 'زيادة',            'sort_order' => 3],
                    ['code' => 'expired',  'label' => 'منتهي الصلاحية',  'sort_order' => 4],
                    ['code' => 'returned', 'label' => 'مرتجع تالف',      'sort_order' => 5],
                    ['code' => 'sample',   'label' => 'عيّنة',            'sort_order' => 6],
                    ['code' => 'other',    'label' => 'أخرى',             'sort_order' => 99],
                ],
            ],

            // ── 9. بلد المنشأ ── companies ────────────────────────────────────
            [
                'code' => 'country',
                'name' => 'بلد المنشأ',
                'description' => 'الدول المتاحة للاختيار',
                'is_system' => false,
                'values' => [
                    ['code' => 'egypt',   'label' => 'مصر',      'sort_order' => 1, 'is_default' => true],
                    ['code' => 'turkey',  'label' => 'تركيا',    'sort_order' => 2],
                    ['code' => 'china',   'label' => 'الصين',    'sort_order' => 3],
                    ['code' => 'italy',   'label' => 'إيطاليا',  'sort_order' => 4],
                    ['code' => 'germany', 'label' => 'ألمانيا',  'sort_order' => 5],
                    ['code' => 'spain',   'label' => 'إسبانيا',  'sort_order' => 6],
                    ['code' => 'india',   'label' => 'الهند',    'sort_order' => 7],
                    ['code' => 'usa',     'label' => 'أمريكا',   'sort_order' => 8],
                    ['code' => 'uae',     'label' => 'الإمارات', 'sort_order' => 9],
                    ['code' => 'saudi',   'label' => 'السعودية', 'sort_order' => 10],
                ],
            ],

        ];

        foreach ($lookups as $lookupData) {
            $values = $lookupData['values'];
            unset($lookupData['values']);

            $type = LookupType::updateOrCreate(
                ['code' => $lookupData['code']],
                $lookupData
            );

            foreach ($values as $value) {
                LookupValue::updateOrCreate(
                    ['lookup_type_id' => $type->id, 'code' => $value['code']],
                    array_merge(['is_default' => false, 'is_active' => true], $value, ['lookup_type_id' => $type->id])
                );
            }
        }
    }
}
