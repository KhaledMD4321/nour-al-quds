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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // asset | liability | equity | revenue | expense
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('chart_of_accounts')
                ->nullOnDelete();
            $table->foreignId('business_unit_id')
                ->nullable()
                ->constrained('business_units')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('level')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('type');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
