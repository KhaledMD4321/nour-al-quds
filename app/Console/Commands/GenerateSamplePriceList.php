<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class GenerateSamplePriceList extends Command
{
    protected $signature   = 'pricelist:sample {--company= : اسم المصنّع في اسم الملف}';
    protected $description = 'إنشاء ملف Excel تجريبي لاختبار رفع قوائم الأسعار';

    public function handle(): int
    {
        $rows = [
            // [كود الصنف, اسم الصنف, السعر, وحدة القياس]
            ['',          'حوض حمام 55 سم أوروبي أبيض',       1250.00, 'piece'],
            ['',          'قاعدة حمام معلّقة ديلوكس',          3500.00, 'piece'],
            ['',          'خلاط حوض كروم ذراع طويل',           650.00,  'piece'],
            ['',          'خلاط مطبخ رقبة مرنة كروم',           850.00,  'piece'],
            ['',          'بانيو جاكوزي 170×80 أبيض',         12000.00, 'piece'],
            ['',          'شطافة كروم مع خرطوم 120 سم',        180.00,  'piece'],
            ['',          'وحدة دش استانلس مع رأس علوي 25 سم', 2200.00, 'set'],
            ['',          'ماسورة PPR 25 مم PN20 للمياه الساخنة', 35.00, 'meter'],
            ['',          'محبس كروي نحاس 3/4 بوصة',            75.00,  'piece'],
            ['',          'صنف جديد تجريبي — اختبار الكود التلقائي', 999.99, 'piece'],
            // صف فيه خطأ مقصود (سعر صفر) — يظهر في قسم "مرفوضة"
            ['',          'صنف خاطئ بسعر صفر',                  0,      'piece'],
        ];

        $export = new class($rows) implements FromArray, WithHeadings {
            public function __construct(private readonly array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return ['كود الصنف', 'اسم الصنف', 'السعر', 'وحدة القياس'];
            }
        };

        $filename = 'sample-price-list.xlsx';
        Excel::store($export, 'public/' . $filename);

        $path = storage_path('app/public/' . $filename);
        $this->info("✅ تم إنشاء الملف التجريبي: {$path}");
        $this->line("   الملف فيه " . count($rows) . " صفوف (منهم 1 خاطئ للاختبار)");

        return self::SUCCESS;
    }
}
