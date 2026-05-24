<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\ChartOfAccount;
use App\Models\Treasury;
use Illuminate\Database\Seeder;

class TreasurySeeder extends Seeder
{
    public function run(): void
    {
        $showroomId = BusinessUnit::where('type', 'showroom')->value('id');
        $distributionId = BusinessUnit::where('type', 'distribution')->value('id');

        // الحسابات المحاسبية المرتبطة — الكودات من الـ CoA الموجودة فعلاً
        $cashShowroom = ChartOfAccount::where('code', '1111')->value('id'); // خزينة المعرض
        $cashDistribution = ChartOfAccount::where('code', '1112')->value('id'); // خزينة التوزيع
        $bankShowroom = ChartOfAccount::where('code', '1114')->value('id'); // بنك المعرض (مضاف في migration 5.1)
        $bankDistribution = ChartOfAccount::where('code', '1115')->value('id'); // بنك التوزيع (مضاف في migration 5.1)

        $treasuries = [
            [
                'name' => 'خزينة المعرض',
                'type' => 'cash',
                'business_unit_id' => $showroomId,
                'account_id' => $cashShowroom,
            ],
            [
                'name' => 'بنك المعرض',
                'type' => 'bank',
                'business_unit_id' => $showroomId,
                'account_id' => $bankShowroom,
            ],
            [
                'name' => 'خزينة التوزيع',
                'type' => 'cash',
                'business_unit_id' => $distributionId,
                'account_id' => $cashDistribution,
            ],
            [
                'name' => 'بنك التوزيع',
                'type' => 'bank',
                'business_unit_id' => $distributionId,
                'account_id' => $bankDistribution,
            ],
        ];

        foreach ($treasuries as $t) {
            Treasury::firstOrCreate(
                [
                    'business_unit_id' => $t['business_unit_id'],
                    'name' => $t['name'],
                ],
                [
                    'type' => $t['type'],
                    'current_balance' => 0,
                    'account_id' => $t['account_id'],
                    'is_active' => true,
                ]
            );
        }
    }
}
