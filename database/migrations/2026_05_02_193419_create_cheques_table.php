<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();

            $table->string('cheque_number', 50);
            $table->string('bank_name', 100);
            $table->decimal('amount', 15, 2);
            $table->date('issue_date');
            $table->date('due_date');

            $table->enum('direction', ['incoming', 'outgoing']);
            $table->enum('status', ['pending', 'deposited', 'collected', 'bounced', 'replaced'])
                  ->default('pending');

            // الخزينة المستهدفة (البنك اللي هيتحصل فيه / هيتخصم منه)
            $table->foreignId('treasury_id')
                  ->nullable()
                  ->constrained('treasuries')
                  ->restrictOnDelete();

            // العميل (للواردة)
            $table->foreignId('customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->restrictOnDelete();

            // المورد (للصادرة)
            $table->foreignId('supplier_id')
                  ->nullable()
                  ->constrained('suppliers')
                  ->restrictOnDelete();

            $table->foreignId('business_unit_id')
                  ->constrained('business_units')
                  ->restrictOnDelete();

            // ربط بسند القبض أو الصرف الأصلي
            $table->foreignId('receipt_id')
                  ->nullable()
                  ->constrained('receipts')
                  ->nullOnDelete();

            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('payments')
                  ->nullOnDelete();

            // ربط بشيك بديل (لو bounced واتعوّض)
            $table->unsignedBigInteger('replaced_by_id')->nullable();

            // تاريخ كل تغيير حالة
            $table->timestamp('deposited_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('bounced_at')->nullable();

            $table->text('bounce_reason')->nullable();
            $table->text('notes')->nullable();

            // القيد المحاسبي المرتبط بهذا الشيك
            $table->foreignId('journal_entry_id')
                  ->nullable()
                  ->constrained('journal_entries')
                  ->nullOnDelete();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // فهارس
            $table->index(['direction', 'status']);
            $table->index(['customer_id', 'due_date']);
            $table->index(['supplier_id', 'due_date']);
            $table->index(['business_unit_id', 'status']);
            $table->index('due_date');
            $table->index('replaced_by_id');

            // foreign key للشيك البديل (self-referential)
            $table->foreign('replaced_by_id')
                  ->references('id')->on('cheques')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
