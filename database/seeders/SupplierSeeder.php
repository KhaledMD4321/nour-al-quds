<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $idealStandard = Company::where('name', 'like', '%إيديال%')->first();
        $duravit = Company::where('name', 'like', '%ديورافيت%')->first();
        $glacier = Company::where('name', 'like', '%جلاسير%')->first();
        $american = Company::where('name', 'like', '%أمريكان%')->first();
        $dima = Company::where('name', 'like', '%ديما%')->first();

        $suppliers = [
            [
                'name' => 'مورد إيديال ستاندرد — فرع القاهرة',
                'phone' => '01055512345',
                'address' => 'المنطقة الصناعية — العاشر من رمضان',
                'company_id' => $idealStandard?->id,
                'tax_registration_number' => '111-222-333',
                'opening_balance' => 0,
                'notes' => 'الموزّع الرسمي لإيديال ستاندرد',
            ],
            [
                'name' => 'مورد ديورافيت',
                'phone' => '01066623456',
                'address' => 'السادس من أكتوبر',
                'company_id' => $duravit?->id,
                'opening_balance' => 15000,
                'notes' => 'عليه رصيد 15 ألف من قبل النظام',
            ],
            [
                'name' => 'مورد جلاسير وخلاطات',
                'phone' => '01077734567',
                'address' => 'العبور — القليوبية',
                'company_id' => $glacier?->id,
                'opening_balance' => 0,
            ],
            [
                'name' => 'شركة ديما للمواسير',
                'phone' => '01088845678',
                'phone_2' => '0228845678',
                'address' => 'بورسعيد',
                'company_id' => $dima?->id,
                'tax_registration_number' => '444-555-666',
                'opening_balance' => 8500,
                'notes' => 'مورد مواسير وتوصيلات',
            ],
            [
                'name' => 'مورد أدوات عامة — أبو حسين',
                'phone' => '01099956789',
                'address' => 'الموسكي — القاهرة',
                'company_id' => null,
                'opening_balance' => 0,
                'notes' => 'مورد متنوع — مش مرتبط بمصنّع محدد',
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::create($data);
        }
    }
}
