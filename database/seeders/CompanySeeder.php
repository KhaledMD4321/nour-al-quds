<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name'           => 'إيديال ستاندرد',
                'country'        => 'مصر',
                'phone'          => '0225678900',
                'representative' => 'أحمد محمود',
                'notes'          => 'أكبر مصنّع أدوات صحية في مصر',
            ],
            [
                'name'           => 'ديورافيت',
                'country'        => 'مصر',
                'phone'          => '0227891234',
                'representative' => 'محمد عبدالله',
                'notes'          => 'أطقم حمامات ومطابخ',
            ],
            [
                'name'           => 'جلاسير',
                'country'        => 'مصر',
                'phone'          => '0223456789',
                'representative' => 'خالد حسن',
                'notes'          => 'خلاطات ووحدات دش',
            ],
            [
                'name'           => 'أمريكان ستاندرد',
                'country'        => 'أمريكا',
                'phone'          => '0229876543',
                'representative' => 'طارق سعيد',
                'notes'          => 'منتجات مستوردة فئة أولى',
            ],
            [
                'name'           => 'ديما',
                'country'        => 'مصر',
                'phone'          => '0226543210',
                'representative' => 'سامي فوزي',
                'notes'          => 'مواسير وتوصيلات PVC',
            ],
        ];

        foreach ($companies as $data) {
            Company::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
