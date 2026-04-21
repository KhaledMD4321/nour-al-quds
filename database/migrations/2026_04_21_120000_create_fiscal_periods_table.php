<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');   // 1-12
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
            $table->index(['year', 'is_locked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
