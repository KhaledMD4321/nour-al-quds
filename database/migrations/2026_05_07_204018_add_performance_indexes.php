<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoices — فلترة بالوحدة + النوع + الحالة معاً (تقارير المبيعات)
        if (! $this->indexExists('invoices', 'invoices_business_unit_id_type_status_index')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['business_unit_id', 'type', 'status'], 'invoices_business_unit_id_type_status_index');
            });
        }

        // invoice_items — JOIN سريع مع الفواتير (أكثر query تتردد)
        if (! $this->indexExists('invoice_items', 'invoice_items_invoice_id_product_id_index')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->index(['invoice_id', 'product_id'], 'invoice_items_invoice_id_product_id_index');
            });
        }

        // journal_entry_lines — SUM المدين والدائن في ميزان المراجعة
        if (! $this->indexExists('journal_entry_lines', 'journal_entry_lines_account_id_business_unit_id_index')) {
            Schema::table('journal_entry_lines', function (Blueprint $table) {
                $table->index(['account_id', 'business_unit_id'], 'journal_entry_lines_account_id_business_unit_id_index');
            });
        }

        // purchase_invoices — تقارير المشتريات حسب المورد والتاريخ
        if (! $this->indexExists('purchase_invoices', 'purchase_invoices_supplier_id_invoice_date_index')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->index(['supplier_id', 'invoice_date'], 'purchase_invoices_supplier_id_invoice_date_index');
            });
        }

        // stock_movements — بحث بالتاريخ في تقارير حركة المخزون
        if (! $this->indexExists('stock_movements', 'stock_movements_created_at_index')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->index(['created_at'], 'stock_movements_created_at_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropIndexIfExists('invoices_business_unit_id_type_status_index'));
        Schema::table('invoice_items', fn (Blueprint $t) => $t->dropIndexIfExists('invoice_items_invoice_id_product_id_index'));
        Schema::table('journal_entry_lines', fn (Blueprint $t) => $t->dropIndexIfExists('journal_entry_lines_account_id_business_unit_id_index'));
        Schema::table('purchase_invoices', fn (Blueprint $t) => $t->dropIndexIfExists('purchase_invoices_supplier_id_invoice_date_index'));
        Schema::table('stock_movements', fn (Blueprint $t) => $t->dropIndexIfExists('stock_movements_created_at_index'));
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?',
            [$table, $index]
        ) !== null;
    }
};
