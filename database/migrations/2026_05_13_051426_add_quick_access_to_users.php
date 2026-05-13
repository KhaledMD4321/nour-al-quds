<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // يخزّن array من الـ keys اللي المستخدم اختارها
            // مثال: ["new_invoice", "quick_sale", "receipt"]
            // لو null → يعرض أول 8 متاحين (default)
            $table->json('quick_access')->nullable()->after('business_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('quick_access');
        });
    }
};
