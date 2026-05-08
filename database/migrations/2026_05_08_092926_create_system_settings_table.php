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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);        // company, invoice, numbering, defaults, alerts, print, business_rules
            $table->string('key', 100);          // unique within group
            $table->text('value')->nullable();   // stored as string (cast per type)
            $table->string('label', 200);        // Arabic label for the UI
            $table->string('type', 30);          // text, number, toggle, select, textarea, file, color
            $table->json('options')->nullable();  // for select: [{"value":"x","label":"ع"}]
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
