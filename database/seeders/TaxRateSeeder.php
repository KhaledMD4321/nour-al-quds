<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['name' => 'ضريبة القيمة المضافة', 'rate' => 14.00, 'is_default' => true,  'is_active' => true],
            ['name' => 'معفى من الضريبة',       'rate' => 0.00, 'is_default' => false, 'is_active' => true],
            ['name' => 'ضريبة جدول',            'rate' => 5.00, 'is_default' => false, 'is_active' => true],
        ];

        foreach ($rates as $data) {
            TaxRate::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
