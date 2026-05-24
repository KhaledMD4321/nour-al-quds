<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Helper: create/update an account and return its model
        $make = function (
            string $code,
            string $name,
            string $type,
            int $level,
            ?int $parentId = null,
            ?int $businessUnitId = null
        ) {
            return ChartOfAccount::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'level' => $level,
                    'parent_id' => $parentId,
                    'business_unit_id' => $businessUnitId,
                    'is_active' => true,
                ]
            );
        };

        // ════════════════════════════════════════════════════════════
        // ASSETS  —  أصول  —  1xxx
        // ════════════════════════════════════════════════════════════

        $assets = $make('1000', 'الأصول', ChartOfAccount::TYPE_ASSET, 1);

        $currentAssets = $make('1100', 'الأصول المتداولة', ChartOfAccount::TYPE_ASSET, 2, $assets->id);

        $cashGroup = $make('1110', 'النقدية والبنوك', ChartOfAccount::TYPE_ASSET, 3, $currentAssets->id);
        $make('1111', 'خزينة المعرض', ChartOfAccount::TYPE_ASSET, 4, $cashGroup->id, 1);
        $make('1112', 'خزينة التوزيع', ChartOfAccount::TYPE_ASSET, 4, $cashGroup->id, 2);
        $make('1113', 'البنك', ChartOfAccount::TYPE_ASSET, 4, $cashGroup->id);

        $receivables = $make('1120', 'العملاء (المدينون)', ChartOfAccount::TYPE_ASSET, 3, $currentAssets->id);
        $make('1121', 'عملاء المعرض', ChartOfAccount::TYPE_ASSET, 4, $receivables->id, 1);
        $make('1122', 'عملاء التوزيع', ChartOfAccount::TYPE_ASSET, 4, $receivables->id, 2);

        $make('1130', 'شيكات تحت التحصيل', ChartOfAccount::TYPE_ASSET, 3, $currentAssets->id);

        $inventory = $make('1140', 'المخزون', ChartOfAccount::TYPE_ASSET, 3, $currentAssets->id);
        $make('1141', 'مخزون المعرض', ChartOfAccount::TYPE_ASSET, 4, $inventory->id, 1);
        $make('1142', 'مخزون التوزيع', ChartOfAccount::TYPE_ASSET, 4, $inventory->id, 2);

        $make('1150', 'سُلف ومصروفات مقدمة', ChartOfAccount::TYPE_ASSET, 3, $currentAssets->id);

        $fixedAssets = $make('1200', 'الأصول الثابتة', ChartOfAccount::TYPE_ASSET, 2, $assets->id);
        $make('1210', 'أثاث وتجهيزات', ChartOfAccount::TYPE_ASSET, 3, $fixedAssets->id);
        $make('1220', 'سيارات', ChartOfAccount::TYPE_ASSET, 3, $fixedAssets->id);
        $make('1230', 'مجمع الإهلاك', ChartOfAccount::TYPE_ASSET, 3, $fixedAssets->id);

        // ════════════════════════════════════════════════════════════
        // LIABILITIES  —  خصوم  —  2xxx
        // ════════════════════════════════════════════════════════════

        $liabilities = $make('2000', 'الخصوم', ChartOfAccount::TYPE_LIABILITY, 1);

        $currentLiabilities = $make('2100', 'الخصوم المتداولة', ChartOfAccount::TYPE_LIABILITY, 2, $liabilities->id);

        $payables = $make('2110', 'الموردون (الدائنون)', ChartOfAccount::TYPE_LIABILITY, 3, $currentLiabilities->id);
        $make('2111', 'موردين المعرض', ChartOfAccount::TYPE_LIABILITY, 4, $payables->id, 1);
        $make('2112', 'موردين التوزيع', ChartOfAccount::TYPE_LIABILITY, 4, $payables->id, 2);

        $make('2120', 'شيكات صادرة', ChartOfAccount::TYPE_LIABILITY, 3, $currentLiabilities->id);
        $make('2130', 'ضرائب مستحقة', ChartOfAccount::TYPE_LIABILITY, 3, $currentLiabilities->id);
        $make('2140', 'مصروفات مستحقة', ChartOfAccount::TYPE_LIABILITY, 3, $currentLiabilities->id);

        // ════════════════════════════════════════════════════════════
        // EQUITY  —  حقوق ملكية  —  3xxx
        // ════════════════════════════════════════════════════════════

        $equity = $make('3000', 'حقوق الملكية', ChartOfAccount::TYPE_EQUITY, 1);
        $make('3100', 'رأس المال', ChartOfAccount::TYPE_EQUITY, 2, $equity->id);
        $make('3200', 'أرباح مُرحّلة', ChartOfAccount::TYPE_EQUITY, 2, $equity->id);
        $make('3300', 'أرباح العام الحالي', ChartOfAccount::TYPE_EQUITY, 2, $equity->id);

        // ════════════════════════════════════════════════════════════
        // REVENUE  —  إيرادات  —  4xxx
        // ════════════════════════════════════════════════════════════

        $revenue = $make('4000', 'الإيرادات', ChartOfAccount::TYPE_REVENUE, 1);

        $salesRevenue = $make('4100', 'إيرادات المبيعات', ChartOfAccount::TYPE_REVENUE, 2, $revenue->id);
        $make('4110', 'مبيعات المعرض', ChartOfAccount::TYPE_REVENUE, 3, $salesRevenue->id, 1);
        $make('4120', 'مبيعات التوزيع', ChartOfAccount::TYPE_REVENUE, 3, $salesRevenue->id, 2);

        $make('4200', 'إيرادات أخرى', ChartOfAccount::TYPE_REVENUE, 2, $revenue->id);

        // ════════════════════════════════════════════════════════════
        // EXPENSES  —  مصروفات  —  5xxx
        // ════════════════════════════════════════════════════════════

        $expenses = $make('5000', 'المصروفات', ChartOfAccount::TYPE_EXPENSE, 1);

        $cogs = $make('5100', 'تكلفة البضاعة المباعة', ChartOfAccount::TYPE_EXPENSE, 2, $expenses->id);
        $make('5110', 'تكلفة مبيعات المعرض', ChartOfAccount::TYPE_EXPENSE, 3, $cogs->id, 1);
        $make('5120', 'تكلفة مبيعات التوزيع', ChartOfAccount::TYPE_EXPENSE, 3, $cogs->id, 2);

        $opex = $make('5200', 'مصروفات تشغيلية', ChartOfAccount::TYPE_EXPENSE, 2, $expenses->id);
        $make('5210', 'إيجارات', ChartOfAccount::TYPE_EXPENSE, 3, $opex->id);
        $make('5220', 'رواتب وأجور', ChartOfAccount::TYPE_EXPENSE, 3, $opex->id);
        $make('5230', 'كهرباء ومياه', ChartOfAccount::TYPE_EXPENSE, 3, $opex->id);
        $make('5240', 'نقل ومواصلات', ChartOfAccount::TYPE_EXPENSE, 3, $opex->id);
        $make('5250', 'صيانة', ChartOfAccount::TYPE_EXPENSE, 3, $opex->id);
        $make('5260', 'أدوات مكتبية', ChartOfAccount::TYPE_EXPENSE, 3, $opex->id);
        $make('5270', 'اتصالات وإنترنت', ChartOfAccount::TYPE_EXPENSE, 3, $opex->id);

        $make('5300', 'مصروفات إدارية', ChartOfAccount::TYPE_EXPENSE, 2, $expenses->id);
        $make('5400', 'مصروفات بنكية', ChartOfAccount::TYPE_EXPENSE, 2, $expenses->id);
        $make('5500', 'إهلاكات', ChartOfAccount::TYPE_EXPENSE, 2, $expenses->id);
    }
}
