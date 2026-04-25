<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                              // CUS-00001 — auto-generated
            $table->string('name');                                         // اسم العميل
            $table->string('phone')->nullable();                            // رقم التليفون
            $table->string('phone_2')->nullable();                          // رقم تليفون ثاني
            $table->text('address')->nullable();                            // العنوان
            $table->string('type')->default('individual');                  // ★ string من lookup — مش enum
            $table->string('tax_registration_number')->nullable();          // رقم التسجيل الضريبي
            $table->decimal('credit_limit', 15, 2)->default(0);             // حد الائتمان — 0 = كاش فقط
            $table->decimal('default_discount_1', 5, 2)->default(0);        // خصم 1 افتراضي %
            $table->decimal('default_discount_2', 5, 2)->default(0);        // خصم 2 افتراضي %
            $table->decimal('default_discount_3', 5, 2)->default(0);        // خصم 3 افتراضي %
            $table->foreignId('business_unit_id')                           // الوحدة التشغيلية (اختياري)
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->decimal('opening_balance', 15, 2)->default(0);          // رصيد افتتاحي (موجب = عليه فلوس)
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
            $table->index('type');
            $table->index('business_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
