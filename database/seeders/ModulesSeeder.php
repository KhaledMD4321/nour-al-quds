<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModulesSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['code' => 'sales',          'name' => 'المبيعات',            'icon' => 'heroicon-o-shopping-cart',       'is_active' => true,  'sort_order' => 1,  'description' => 'الفواتير وعروض الأسعار والمرتجعات والبيع السريع'],
            ['code' => 'purchases',      'name' => 'المشتريات',           'icon' => 'heroicon-o-truck',                'is_active' => true,  'sort_order' => 2,  'description' => 'فواتير المشتريات ومرتجعاتها'],
            ['code' => 'inventory',      'name' => 'المخزون',             'icon' => 'heroicon-o-cube',                 'is_active' => true,  'sort_order' => 3,  'description' => 'أرصدة المخزون والتسويات والتحويلات والحركات'],
            ['code' => 'customers',      'name' => 'العملاء والموردين',   'icon' => 'heroicon-o-users',                'is_active' => true,  'sort_order' => 4,  'description' => 'إدارة العملاء والموردين'],
            ['code' => 'finance',        'name' => 'الخزينة والمالية',    'icon' => 'heroicon-o-banknotes',            'is_active' => true,  'sort_order' => 5,  'description' => 'الخزائن والبنوك وسندات القبض والصرف والشيكات'],
            ['code' => 'accounting',     'name' => 'المحاسبة',            'icon' => 'heroicon-o-calculator',           'is_active' => true,  'sort_order' => 6,  'description' => 'القيود اليومية ودليل الحسابات والفترات المالية'],
            ['code' => 'reports',        'name' => 'التقارير',            'icon' => 'heroicon-o-chart-bar',            'is_active' => true,  'sort_order' => 7,  'description' => 'تقارير المبيعات والمشتريات والأرباح والمخزون'],
            ['code' => 'catalog',        'name' => 'الشركات والأصناف',   'icon' => 'heroicon-o-tag',                  'is_active' => true,  'sort_order' => 8,  'description' => 'المنتجات والتصنيفات وقوائم الأسعار'],
            ['code' => 'data_mgmt',      'name' => 'إدارة البيانات',      'icon' => 'heroicon-o-circle-stack',         'is_active' => true,  'sort_order' => 9,  'description' => 'الاستيراد والتصدير وإدارة الفترات وتنظيف البيانات'],
            ['code' => 'internal_ops',   'name' => 'العمليات الداخلية',   'icon' => 'heroicon-o-arrows-right-left',    'is_active' => true,  'sort_order' => 10, 'description' => 'التحويلات بين الوحدات التشغيلية'],
        ];

        foreach ($modules as $row) {
            Module::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
