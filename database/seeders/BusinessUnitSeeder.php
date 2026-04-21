<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use Illuminate\Database\Seeder;

class BusinessUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'معرض نور القدس',       'type' => BusinessUnit::TYPE_SHOWROOM],
            ['name' => 'مخزن التوزيع الرئيسي', 'type' => BusinessUnit::TYPE_DISTRIBUTION],
        ];

        foreach ($units as $unit) {
            BusinessUnit::updateOrCreate(
                ['type' => $unit['type']],
                ['name' => $unit['name'], 'is_active' => true]
            );
        }
    }
}
