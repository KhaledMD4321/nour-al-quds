<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $showroom     = BusinessUnit::where('type', 'showroom')->first();
        $distribution = BusinessUnit::where('type', 'distribution')->first();

        $customers = [
            // ─── عملاء المعرض (تجزئة) ───────────────────────────────────────────
            [
                'name'               => 'أحمد محمد علي',
                'phone'              => '01012345678',
                'address'            => 'مدينة نصر — القاهرة',
                'type'               => 'individual',
                'credit_limit'       => 0,
                'default_discount_1' => 0,
                'default_discount_2' => 0,
                'default_discount_3' => 0,
                'business_unit_id'   => $showroom?->id,
                'notes'              => 'عميل كاش — تجزئة',
            ],
            [
                'name'                   => 'شركة المقاولات المصرية',
                'phone'                  => '01123456789',
                'phone_2'                => '0223456789',
                'address'                => 'مصر الجديدة — القاهرة',
                'type'                   => 'company',
                'tax_registration_number'=> '123-456-789',
                'credit_limit'           => 50000,
                'default_discount_1'     => 10,
                'default_discount_2'     => 5,
                'default_discount_3'     => 0,
                'business_unit_id'       => $showroom?->id,
                'notes'                  => 'شركة مقاولات كبيرة — بيع آجل',
            ],
            [
                'name'               => 'المهندس كريم حسن',
                'phone'              => '01234567890',
                'address'            => 'التجمع الخامس',
                'type'               => 'contractor',
                'credit_limit'       => 20000,
                'default_discount_1' => 5,
                'default_discount_2' => 0,
                'default_discount_3' => 0,
                'business_unit_id'   => $showroom?->id,
                'notes'              => 'مقاول تشطيبات',
            ],

            // ─── عملاء التوزيع (جملة) ───────────────────────────────────────────
            [
                'name'                   => 'معرض الأمل للسيراميك والأدوات الصحية',
                'phone'                  => '01098765432',
                'phone_2'                => '0228765432',
                'address'                => 'شارع الجمهورية — طنطا',
                'type'                   => 'trader',
                'tax_registration_number'=> '987-654-321',
                'credit_limit'           => 100000,
                'default_discount_1'     => 15,
                'default_discount_2'     => 5,
                'default_discount_3'     => 2,
                'business_unit_id'       => $distribution?->id,
                'notes'                  => 'تاجر جملة — خصم مميز',
            ],
            [
                'name'               => 'محلات النور للسباكة',
                'phone'              => '01187654321',
                'address'            => 'المنصورة — الدقهلية',
                'type'               => 'trader',
                'credit_limit'       => 75000,
                'default_discount_1' => 12,
                'default_discount_2' => 3,
                'default_discount_3' => 0,
                'business_unit_id'   => $distribution?->id,
                'notes'              => 'تاجر جملة',
            ],
            [
                'name'                   => 'جهة حكومية — مستشفى القصر العيني',
                'phone'                  => '0223654789',
                'address'                => 'القصر العيني — القاهرة',
                'type'                   => 'government',
                'tax_registration_number'=> '456-789-123',
                'credit_limit'           => 200000,
                'default_discount_1'     => 20,
                'default_discount_2'     => 5,
                'default_discount_3'     => 0,
                'business_unit_id'       => $distribution?->id,
                'notes'                  => 'عقد توريد حكومي',
            ],
            [
                'name'               => 'نقدي عام',
                'phone'              => null,
                'address'            => null,
                'type'               => 'individual',
                'credit_limit'       => 0,
                'default_discount_1' => 0,
                'default_discount_2' => 0,
                'default_discount_3' => 0,
                'business_unit_id'   => null,
                'notes'              => 'عميل نقدي افتراضي — للبيع بدون تسجيل اسم العميل',
            ],
        ];

        foreach ($customers as $data) {
            Customer::create($data);
        }
    }
}
