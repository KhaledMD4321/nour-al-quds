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
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('treasury_id')
                  ->constrained('treasuries')
                  ->restrictOnDelete();

            $table->enum('type', ['receipt', 'payment', 'transfer_in', 'transfer_out']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2); // إلزامي — سجل أبدي

            $table->date('transaction_date');
            $table->text('description');

            // Polymorphic — يتربط بأي معاملة (Receipt, Payment, QuickSale, Cheque, TreasuryTransfer)
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // بدون updated_at — السجل لا يتعدّل
            // بدون softDeletes — السجل أبدي
            $table->timestamp('created_at')->useCurrent();

            $table->index(['treasury_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
