<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط الفاتورة المحوّلة بعرض السعر الأصلي، لحساب معدل تحويل العروض
 * (win-rate) بدقة. عمود اختياري — لا يمسّ البيانات القائمة.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'quotation_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('quotation_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoices', 'quotation_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('quotation_id');
        });
    }
};
