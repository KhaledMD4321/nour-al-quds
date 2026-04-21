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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_unit_id')
                ->nullable()
                ->after('email')
                ->constrained('business_units')
                ->nullOnDelete();

            $table->boolean('is_active')
                ->default(true)
                ->after('business_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn(['business_unit_id', 'is_active']);
        });
    }
};
