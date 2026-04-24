<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');                      // رقم الإصدار: 1, 2, 3…
            $table->date('effective_date');                         // تاريخ سريان اللستة
            $table->enum('status', ['active', 'archived'])          // ★ enum — مرتبط بسلوك الكود
                  ->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            // إصدار واحد لكل رقم لكل مصنّع
            $table->unique(['company_id', 'version_number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_versions');
    }
};
