<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        CompanySetting::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'شركة نور القدس للأدوات الصحية والسباكة',
                'phone' => '01000000000',
                'address' => 'مصر',
                'default_currency' => 'EGP',
            ]
        );
    }
}
