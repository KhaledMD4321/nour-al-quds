<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique(); // UTR-XXXXX

            // الوحدة المصدر
            $table->foreignId('from_business_unit_id')->constrained('business_units');
            $table->foreignId('from_warehouse_id')->constrained('warehouses');

            // الوحدة الوجهة
            $table->foreignId('to_business_unit_id')->constrained('business_units');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');

            $table->date('transfer_date');
            $table->string('status', 20)->default('draft');

            // الفاتورتين المولّدتين تلقائياً
            $table->unsignedBigInteger('sale_invoice_id')->nullable();
            $table->unsignedBigInteger('purchase_invoice_id')->nullable();

            $table->decimal('total_amount', 15, 2)->default(0);

            // أساس تحديد سعر التحويل
            $table->string('transfer_price_type', 20)->default('avg_cost');
            // avg_cost = متوسط التكلفة | list_price = سعر القائمة | custom = سعر مخصص

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'transfer_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_transfers');
    }
};
