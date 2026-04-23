<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Pull IDs by name so the seeder stays safe even if row IDs shift
        $companies  = Company::pluck('id', 'name');
        $categories = Category::pluck('id', 'name');

        $products = [
            // ── Ideal Standard ────────────────────────────────────────────────
            [
                'name'             => 'حوض حمام إيديال ستاندرد 60 سم أبيض',
                'company_id'       => $companies['إيديال ستاندرد'] ?? null,
                'category_id'      => $categories['أحواض'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 1850.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'مرحاض إيديال ستاندرد أرضي كامل مع خزان',
                'company_id'       => $companies['إيديال ستاندرد'] ?? null,
                'category_id'      => $categories['مراحيض'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 3200.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'خلاط حوض حمام إيديال ستاندرد كروم',
                'company_id'       => $companies['إيديال ستاندرد'] ?? null,
                'category_id'      => $categories['خلاطات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 750.00,
                'is_active'        => true,
            ],

            // ── Grohe ─────────────────────────────────────────────────────────
            [
                'name'             => 'خلاط مطبخ جروهي Eurosmart كروم',
                'company_id'       => $companies['جروهي'] ?? null,
                'category_id'      => $categories['خلاطات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 1450.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'دش جروهي Rainshower 300 مم كروم',
                'company_id'       => $companies['جروهي'] ?? null,
                'category_id'      => $categories['دشات وملحقات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 2100.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'خلاط دش جروهي Grohtherm كروم',
                'company_id'       => $companies['جروهي'] ?? null,
                'category_id'      => $categories['خلاطات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 3800.00,
                'is_active'        => true,
            ],

            // ── Geberit ───────────────────────────────────────────────────────
            [
                'name'             => 'خزان جيبريت مدفون UP320 للمرحاض',
                'company_id'       => $companies['جيبريت'] ?? null,
                'category_id'      => $categories['خزانات وإكسسوار مراحيض'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 2650.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'لوحة ضغط جيبريت Sigma01 أبيض',
                'company_id'       => $companies['جيبريت'] ?? null,
                'category_id'      => $categories['خزانات وإكسسوار مراحيض'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 680.00,
                'is_active'        => true,
            ],

            // ── Roca ──────────────────────────────────────────────────────────
            [
                'name'             => 'حوض حمام روكا Gap 55 سم أبيض',
                'company_id'       => $companies['روكا'] ?? null,
                'category_id'      => $categories['أحواض'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 1600.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'مرحاض روكا Gap معلّق بالحائط',
                'company_id'       => $companies['روكا'] ?? null,
                'category_id'      => $categories['مراحيض'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 2900.00,
                'is_active'        => true,
            ],

            // ── Generic / Unbranded ───────────────────────────────────────────
            [
                'name'             => 'ماسورة PPR مياه ساخنة 20 مم (بالمتر)',
                'company_id'       => null,
                'category_id'      => $categories['مواسير وتوصيلات'] ?? null,
                'unit_of_measure'  => 'meter',
                'list_price'       => 18.50,
                'is_active'        => true,
            ],
            [
                'name'             => 'ماسورة PPR مياه باردة 25 مم (بالمتر)',
                'company_id'       => null,
                'category_id'      => $categories['مواسير وتوصيلات'] ?? null,
                'unit_of_measure'  => 'meter',
                'list_price'       => 14.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'كوع PPR 20×90 درجة',
                'company_id'       => null,
                'category_id'      => $categories['مواسير وتوصيلات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 3.50,
                'is_active'        => true,
            ],
            [
                'name'             => 'محبس كروم ربع لفة 1/2 بوصة',
                'company_id'       => null,
                'category_id'      => $categories['محابس وصمامات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 45.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'محبس زاوية كروم 1/2 بوصة',
                'company_id'       => null,
                'category_id'      => $categories['محابس وصمامات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 38.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'سيفون أرضية بلاستيك مربع 10×10',
                'company_id'       => null,
                'category_id'      => $categories['سيفونات وصرف'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 22.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'سيفون حوض حمام مع صرف زاوية كروم',
                'company_id'       => null,
                'category_id'      => $categories['سيفونات وصرف'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 85.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'خرطوم دش مرن 150 سم كروم',
                'company_id'       => null,
                'category_id'      => $categories['دشات وملحقات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 55.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'إكسسوار حمام 5 قطع كروم (حامل مناشف + ورق + صابون)',
                'company_id'       => null,
                'category_id'      => $categories['إكسسوار حمام'] ?? null,
                'unit_of_measure'  => 'set',
                'list_price'       => 320.00,
                'is_active'        => true,
            ],
            [
                'name'             => 'مرآة حمام مضيئة LED 60×80 سم',
                'company_id'       => null,
                'category_id'      => $categories['مرايا وكابينات'] ?? null,
                'unit_of_measure'  => 'piece',
                'list_price'       => 950.00,
                'is_active'        => true,
            ],
        ];

        foreach ($products as $data) {
            // Use name as natural key — avoid duplicates on re-seed
            Product::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
