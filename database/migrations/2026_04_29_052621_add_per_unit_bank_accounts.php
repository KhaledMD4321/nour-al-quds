<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * يضيف حسابَي بنك منفصلَين لكل وحدة تشغيلية.
     * الكودات 1114 و 1115 تحت أبوهم 1110 (النقدية والبنوك, id=3).
     * الـ 1113 (البنك المشترك) يبقى موجوداً للتوافق مع البيانات القديمة.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $parentId = DB::table('chart_of_accounts')->where('code', '1110')->value('id');
            $showroomId = DB::table('business_units')->where('type', 'showroom')->value('id');
            $distributionId = DB::table('business_units')->where('type', 'distribution')->value('id');

            $accounts = [
                [
                    'code' => '1114',
                    'name' => 'بنك المعرض',
                    'type' => 'asset',
                    'parent_id' => $parentId,
                    'business_unit_id' => $showroomId,
                    'level' => 4,
                    'is_active' => true,
                ],
                [
                    'code' => '1115',
                    'name' => 'بنك التوزيع',
                    'type' => 'asset',
                    'parent_id' => $parentId,
                    'business_unit_id' => $distributionId,
                    'level' => 4,
                    'is_active' => true,
                ],
            ];

            foreach ($accounts as $a) {
                DB::table('chart_of_accounts')->updateOrInsert(
                    ['code' => $a['code']],
                    array_merge($a, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        });
    }

    public function down(): void
    {
        DB::table('chart_of_accounts')
            ->whereIn('code', ['1114', '1115'])
            ->update(['is_active' => false]);
    }
};
