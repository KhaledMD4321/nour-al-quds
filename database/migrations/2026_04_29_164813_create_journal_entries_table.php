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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 30)->unique(); // JE-XXXXXX

            $table->date('entry_date');
            $table->text('description');

            // المصدر الذي ولّد القيد (polymorphic)
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->boolean('is_manual')->default(false);
            $table->boolean('is_posted')->default(true); // مرحلياً كله مُرحَّل فوراً

            $table->decimal('total_debit',  15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_type', 'source_id']);
            $table->index(['entry_date', 'is_posted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
