<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();         // PRD-00001 — auto-generated
            $table->string('name');                   // اسم الصنف
            $table->foreignId('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->nullOnDelete();
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();
            $table->string('unit_of_measure');        // lookup code: 'piece', 'meter', …
            $table->decimal('list_price', 15, 2)->default(0);
            $table->string('image')->nullable();      // relative path under storage/app/public
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────────────
            $table->index('name');                    // partial-name search (ILIKE)
            $table->index('company_id');
            $table->index('category_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
