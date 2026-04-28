<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();  // PRR-XXXXX

            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('business_unit_id')->constrained('business_units');

            $table->date('return_date');
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
