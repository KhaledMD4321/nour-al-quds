<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PriceListItem;
use App\Models\PriceListVersion;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        // لكل مصنّع عنده أصناف: أنشئ إصدار نشط وسجّل أسعار أصنافه
        $companies = Company::with('products')->get();

        foreach ($companies as $company) {
            if ($company->products->isEmpty()) {
                continue;
            }

            // تجنّب التكرار لو الـ seeder اتشغّل أكتر من مرة
            $exists = PriceListVersion::where('company_id', $company->id)
                                      ->where('version_number', 1)
                                      ->exists();
            if ($exists) {
                continue;
            }

            $version = PriceListVersion::create([
                'company_id'     => $company->id,
                'version_number' => 1,
                'effective_date' => now()->toDateString(),
                'status'         => 'active',
                'notes'          => 'قائمة أسعار افتتاحية',
            ]);

            foreach ($company->products as $product) {
                $price = $this->estimatePrice($product->name);

                PriceListItem::create([
                    'version_id' => $version->id,
                    'product_id' => $product->id,
                    'price'      => $price,
                ]);
            }
        }
    }

    /**
     * أسعار تقديرية واقعية — مجرد seed data للاختبار.
     */
    private function estimatePrice(string $productName): float
    {
        $name = mb_strtolower($productName);

        if (str_contains($name, 'بانيو'))               return (float) rand(2500, 8000);
        if (str_contains($name, 'قاعدة'))               return (float) rand(1500, 6000);
        if (str_contains($name, 'حوض'))                 return (float) rand(800,  4000);
        if (str_contains($name, 'خلاط'))                return (float) rand(300,  2500);
        if (str_contains($name, 'دش') && str_contains($name, 'وحدة')) return (float) rand(1500, 5000);
        if (str_contains($name, 'دش'))                  return (float) rand(200,  1500);
        if (str_contains($name, 'شطاف'))                return (float) rand(100,   500);
        if (str_contains($name, 'ماسورة'))              return (float) rand(10,    150);
        if (str_contains($name, 'كوع') || str_contains($name, 'تيه')) return (float) rand(3, 40);
        if (str_contains($name, 'محبس'))                return (float) rand(30,    150);
        if (str_contains($name, 'سيفون'))               return (float) rand(20,    120);
        if (str_contains($name, 'سخان'))                return (float) rand(2000, 6000);
        if (str_contains($name, 'إكسسوار'))             return (float) rand(200,   800);
        if (str_contains($name, 'مرآة'))                return (float) rand(500,  2000);
        if (str_contains($name, 'خزان'))                return (float) rand(1500, 4000);
        if (str_contains($name, 'لوحة'))                return (float) rand(400,  1000);
        if (str_contains($name, 'مرحاض'))               return (float) rand(1500, 5000);
        if (str_contains($name, 'خرطوم'))               return (float) rand(40,    200);

        return (float) rand(50, 500);
    }
}
