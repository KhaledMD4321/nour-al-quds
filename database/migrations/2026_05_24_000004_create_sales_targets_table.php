<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أهداف المبيعات الشهرية لكل وحدة تشغيلية — لمقارنة الأداء الفعلي بالمستهدف.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_targets')) {
            return;
        }

        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('month'); // 1..12
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['business_unit_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
