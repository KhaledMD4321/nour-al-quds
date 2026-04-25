<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);   // الكمية الحالية
            $table->decimal('avg_cost', 15, 4)->default(0);   // متوسط التكلفة المرجح
            $table->timestamp('last_updated')->nullable();

            // كل صنف مرة واحدة في كل مخزن
            $table->unique(['warehouse_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
