<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة 'internal' لقائمة أنواع الدفع المسموح بها
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_payment_type_check');
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_payment_type_check
            CHECK (payment_type IN ('cash', 'credit', 'cheque', 'internal'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_payment_type_check');
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_payment_type_check
            CHECK (payment_type IN ('cash', 'credit', 'cheque'))
        ");
    }
};
