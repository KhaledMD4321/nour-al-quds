<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. أي quick_sale بـ treasury_id = NULL بنربطه بأول خزينة كاش لنفس الوحدة
        //    (البيانات دي من قبل ربط الخزائن — نعاملها كخزينة المعرض الافتراضية)
        $nullCount = DB::table('quick_sales')->whereNull('treasury_id')->count();

        if ($nullCount > 0) {
            $rows = DB::table('quick_sales')
                ->whereNull('treasury_id')
                ->select('id', 'business_unit_id')
                ->get();

            foreach ($rows as $row) {
                $treasuryId = DB::table('treasuries')
                    ->where('business_unit_id', $row->business_unit_id)
                    ->where('type', 'cash')
                    ->where('is_active', true)
                    ->value('id');

                if ($treasuryId) {
                    DB::table('quick_sales')
                        ->where('id', $row->id)
                        ->update(['treasury_id' => $treasuryId]);
                }
            }

            // تحقق من أنه تم إصلاح الكل
            $stillNull = DB::table('quick_sales')->whereNull('treasury_id')->count();
            if ($stillNull > 0) {
                throw new Exception(
                    "لا يزال هناك {$stillNull} سجل بـ treasury_id = NULL. تأكد من وجود خزائن كاش أولاً."
                );
            }
        }

        // 2. تحويل العمود لـ NOT NULL
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->foreignId('treasury_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->foreignId('treasury_id')->nullable()->change();
        });
    }
};
