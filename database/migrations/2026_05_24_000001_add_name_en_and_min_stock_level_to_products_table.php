<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة عمودين كان الكود يشير إليهما دون وجودهما فعلياً في الجدول:
 *   - name_en          : الاسم بالإنجليزي (مُستخدم في التصدير/الاستيراد)
 *   - min_stock_level  : الحد الأدنى للمخزون (يُفعّل تنبيهات نقص المخزون LowStockWidget)
 *
 * هجرة إضافية غير مدمّرة — لا تمسّ أي بيانات قائمة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'name_en')) {
                $table->string('name_en')->nullable();
            }

            if (! Schema::hasColumn('products', 'min_stock_level')) {
                $table->decimal('min_stock_level', 15, 3)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['name_en', 'min_stock_level'],
                fn (string $column) => Schema::hasColumn('products', $column)
            ));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
