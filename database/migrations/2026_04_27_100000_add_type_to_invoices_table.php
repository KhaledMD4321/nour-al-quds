<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type', 20)->default('sale')->after('reference_number');
        });

        // قيد التحقق من نوع الفاتورة
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_type_check
            CHECK (type IN ('sale', 'sale_return', 'quotation'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_type_check');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
