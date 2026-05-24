<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            $table->foreignId('product_id')->constrained('products');

            // ── الكمية والأسعار ──────────────────────────────────────────────
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('list_price', 15, 2)->default(0);  // سعر اللستة قبل الخصم

            // ── الخصم الثلاثي المتتابع ───────────────────────────────────────
            $table->decimal('discount_1', 6, 2)->default(0);
            $table->decimal('discount_2', 6, 2)->default(0);
            $table->decimal('discount_3', 6, 2)->default(0);

            $table->decimal('unit_price', 15, 2)->default(0);  // بعد تطبيق الخصومات
            $table->decimal('total', 15, 2)->default(0);  // unit_price × quantity

            // ── مرجع قائمة الأسعار المستخدمة ────────────────────────────────
            $table->foreignId('price_list_version_id')
                ->nullable()
                ->constrained('price_list_versions');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
