<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── إصلاح labels الفاضية في expense_category lookup ─────────────────
        $typeId = DB::table('lookup_types')->where('code', 'expense_category')->value('id');
        if ($typeId) {
            $labels = [
                'supplier_payment' => 'دفع مورد',
                'rent'             => 'إيجار',
                'salary'           => 'رواتب وأجور',
                'transport'        => 'نقل وشحن',
                'electricity'      => 'كهرباء',
                'water'            => 'مياه',
                'phone'            => 'اتصالات',
                'maintenance'      => 'صيانة',
                'office_supplies'  => 'أدوات مكتبية',
                'taxes'            => 'ضرائب ورسوم',
                'other'            => 'مصروفات أخرى',
            ];
            foreach ($labels as $code => $label) {
                DB::table('lookup_values')
                    ->where('lookup_type_id', $typeId)
                    ->where('code', $code)
                    ->update(['label' => $label]);
            }
        }

        // ── جدول payments ─────────────────────────────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique(); // PAY-XXXXX

            // الخزينة — nullable للشيكات
            $table->foreignId('treasury_id')
                  ->nullable()
                  ->constrained('treasuries')
                  ->restrictOnDelete();

            // المورد — إلزامي لو category = supplier_payment
            $table->foreignId('supplier_id')
                  ->nullable()
                  ->constrained('suppliers')
                  ->restrictOnDelete();

            // فاتورة المشتريات (اختياري)
            $table->foreignId('purchase_invoice_id')
                  ->nullable()
                  ->constrained('purchase_invoices')
                  ->restrictOnDelete();

            $table->foreignId('business_unit_id')
                  ->constrained('business_units')
                  ->restrictOnDelete();

            $table->decimal('amount', 15, 2);

            // من expense_category lookup: supplier_payment, rent, salary, ...
            $table->string('category', 50);

            $table->enum('payment_method', ['cash', 'cheque', 'bank_transfer']);
            $table->date('payment_date');

            // بيانات الشيك — JSON مؤقت (Phase 5.4 الجدول الكامل)
            $table->json('cheque_details')->nullable();

            $table->string('bank_reference', 100)->nullable();

            // حساب المصروف من شجرة الحسابات (للمصروفات التشغيلية)
            $table->foreignId('expense_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts')
                  ->restrictOnDelete();

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

            $table->index(['supplier_id', 'payment_date']);
            $table->index(['business_unit_id', 'payment_date']);
            $table->index(['category', 'payment_date']);
            $table->index('purchase_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        // لا نعكس إصلاح labels — بياناتها صح
    }
};
