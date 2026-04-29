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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 30)->unique(); // REC-XXXXX

            // الخزينة المستلمة (للكاش والتحويل البنكي — nullable للشيكات)
            $table->foreignId('treasury_id')
                  ->nullable()
                  ->constrained('treasuries')
                  ->restrictOnDelete();

            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->restrictOnDelete();

            // الفاتورة المرتبطة (اختياري — للتحصيل على الحساب)
            $table->foreignId('invoice_id')
                  ->nullable()
                  ->constrained('invoices')
                  ->restrictOnDelete();

            $table->foreignId('business_unit_id')
                  ->constrained('business_units')
                  ->restrictOnDelete();

            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'cheque', 'bank_transfer']);
            $table->date('receipt_date');

            // بيانات الشيك — مؤقتاً JSON (دورة الحياة الكاملة في Phase 5.4)
            // الشكل: {"cheque_number":"...","bank_name":"...","due_date":"YYYY-MM-DD"}
            $table->json('cheque_details')->nullable();

            // مرجع التحويل البنكي
            $table->string('bank_reference', 100)->nullable();

            $table->text('notes')->nullable();

            // القيد المحاسبي المولَّد تلقائياً
            $table->foreignId('journal_entry_id')
                  ->nullable()
                  ->constrained('journal_entries')
                  ->nullOnDelete();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'receipt_date']);
            $table->index(['business_unit_id', 'receipt_date']);
            $table->index(['payment_method', 'receipt_date']);
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
