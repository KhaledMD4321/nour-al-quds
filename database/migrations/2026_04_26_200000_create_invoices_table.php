<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();

            // ── العلاقات ────────────────────────────────────────────────────
            $table->foreignId('business_unit_id')->constrained('business_units');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('created_by')->nullable()->constrained('users');

            // ── الحالة ──────────────────────────────────────────────────────
            $table->enum('status', [
                'draft',
                'confirmed',
                'delivered',
                'partially_paid',
                'paid',
                'cancelled',
            ])->default('draft');

            // ── طريقة الدفع ─────────────────────────────────────────────────
            $table->enum('payment_type', ['cash', 'credit', 'cheque'])->default('cash');

            // ── الأرقام ──────────────────────────────────────────────────────
            $table->decimal('subtotal',        15, 2)->default(0);  // قبل الخصم والضريبة
            $table->decimal('discount_amount', 15, 2)->default(0);  // خصم إضافي على الفاتورة
            $table->decimal('tax_amount',      15, 2)->default(0);  // ضريبة (للمستقبل)
            $table->decimal('total_amount',    15, 2)->default(0);  // الإجمالي النهائي
            $table->decimal('paid_amount',     15, 2)->default(0);  // المدفوع

            // ── للفواتير المصحَّحة ──────────────────────────────────────────
            $table->foreignId('original_invoice_id')->nullable()->constrained('invoices');

            // ── بيانات إضافية ────────────────────────────────────────────────
            $table->date('invoice_date')->default(DB::raw('CURRENT_DATE'));
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['business_unit_id', 'created_at']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
