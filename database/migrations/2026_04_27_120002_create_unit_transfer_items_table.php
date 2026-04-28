<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_transfer_id')
                  ->constrained('unit_transfers')
                  ->cascadeOnDelete();

            $table->foreignId('product_id')->constrained('products');

            $table->decimal('quantity',   15, 3);
            $table->decimal('unit_price', 15, 4); // سعر التحويل الداخلي
            $table->decimal('total',      15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_transfer_items');
    }
};
