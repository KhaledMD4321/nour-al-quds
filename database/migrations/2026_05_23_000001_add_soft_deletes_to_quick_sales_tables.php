<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة soft deletes لجداول البيع السريع
 * (تتوافق مع قاعدة "أي حذف = soft delete + أرشفة")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('quick_sale_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('quick_sale_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
