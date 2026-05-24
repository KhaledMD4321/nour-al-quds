<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('version_id')
                ->constrained('price_list_versions')
                ->cascadeOnDelete();         // لو الإصدار اتحذف، البنود تتحذف معاه
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 15, 4);   // سعر القائمة بدقة 4 خانات عشرية
            $table->timestamps();

            // كل صنف مرة واحدة في الإصدار
            $table->unique(['version_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
