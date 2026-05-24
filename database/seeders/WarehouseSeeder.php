<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $showroom = BusinessUnit::where('type', 'showroom')->first();
        $distribution = BusinessUnit::where('type', 'distribution')->first();

        // مخزن المعرض
        if ($showroom) {
            Warehouse::firstOrCreate(
                ['name' => 'مخزن المعرض', 'business_unit_id' => $showroom->id],
                [
                    'location' => 'داخل المعرض — الطابق الأرضي',
                    'notes' => 'مخزن بيع التجزئة — كميات محدودة',
                    'is_active' => true,
                ]
            );
        }

        // مخزن التوزيع الرئيسي
        if ($distribution) {
            Warehouse::firstOrCreate(
                ['name' => 'مخزن التوزيع الرئيسي', 'business_unit_id' => $distribution->id],
                [
                    'location' => 'المنطقة الصناعية — المخزن الكبير',
                    'notes' => 'مخزن الجملة الأساسي',
                    'is_active' => true,
                ]
            );
        }
    }
}
