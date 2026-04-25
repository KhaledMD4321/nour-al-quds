<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');                               // "مخزن المعرض" / "مخزن التوزيع الرئيسي"
            $table->foreignId('business_unit_id')->constrained(); // كل مخزن تابع لوحدة تشغيلية
            $table->string('location')->nullable();               // العنوان أو المكان
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('business_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
