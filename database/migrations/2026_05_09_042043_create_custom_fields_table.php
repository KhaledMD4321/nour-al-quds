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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();

            $table->string('entity_type', 50);   // customer, supplier, product, company, invoice
            $table->string('field_key', 80);      // machine name: color, national_id
            $table->string('field_label', 150);   // Arabic label: اللون, رقم الهوية
            $table->string('field_type', 30);     // text, number, date, select, toggle, textarea
            $table->json('options')->nullable();   // لو select: ["أبيض","بيج","كروم"]
            $table->text('default_value')->nullable();
            $table->text('placeholder')->nullable();

            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(false);  // يظهر في فلاتر الجدول
            $table->boolean('is_printable')->default(false);   // يظهر في الطباعة
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['entity_type', 'field_key']);
            $table->index(['entity_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
