<?php

namespace App\Modules\Accounting;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Receipt;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * LedgerService — المصدر الوحيد لحساب أرصدة وكشوف حساب العملاء والموردين.
 *
 * يوحّد المنطق الذي كان مكرراً (ومتضارباً) في:
 *   - Customer/Supplier::getCurrentBalanceAttribute()
 *   - CustomerStatement / SupplierStatement (صفحات Filament)
 *   - CustomerStatementPrintController / SupplierStatementPrintController
 *
 * ── القواعد القانونية (canonical) ──────────────────────────────────────────
 *  العميل (طبيعته مدينة، موجب = مستحق علينا تحصيله):
 *     + فواتير البيع   (type=sale,        status ∉ {draft, cancelled})  → مدين
 *     − مرتجعات البيع  (type=sale_return, status ∉ {draft, cancelled})  → دائن
 *     − سندات القبض                                                      → دائن
 *
 *  المورد (طبيعته دائنة، موجب = مستحق له):
 *     + فواتير الشراء  (status ∈ {confirmed, paid})                      → دائن
 *     − مرتجعات الشراء (status = confirmed)                              → مدين
 *     − سندات الصرف    (category = supplier_payment)                     → مدين
 */
class LedgerService
{
    // ════════════════════════════════════════════════════════════════════
    //  العملاء
    // ════════════════════════════════════════════════════════════════════

    /**
     * الرصيد الحالي للعميل (كل الحركات، بدون فلتر تاريخ).
     * موجب = مدين (مستحق علينا تحصيله من العميل).
     */
    public function customerBalance(int $customerId): float
    {
        $opening = (float) (Customer::whereKey($customerId)->value('opening_balance') ?? 0);
        $sales = (float) $this->customerSales($customerId)->sum('total_amount');
        $returns = (float) $this->customerReturns($customerId)->sum('total_amount');
        $receipts = (float) $this->customerReceipts($customerId)->sum('amount');

        return round($opening + $sales - $returns - $receipts, 2);
    }

    /**
     * كشف حساب العميل خلال فترة.
     *
     * @return array{opening: float, lines: Collection, totalDebit: float, totalCredit: float, closing: float}
     */
    public function customerStatement(int $customerId, ?string $from = null, ?string $to = null): array
    {
        // ── رصيد أول المدة ──────────────────────────────────────────────
        $opening = (float) (Customer::whereKey($customerId)->value('opening_balance') ?? 0);

        if ($from) {
            $opening += (float) $this->customerSales($customerId)->whereDate('invoice_date', '<', $from)->sum('total_amount');
            $opening -= (float) $this->customerReturns($customerId)->whereDate('invoice_date', '<', $from)->sum('total_amount');
            $opening -= (float) $this->customerReceipts($customerId)->whereDate('receipt_date', '<', $from)->sum('amount');
        }

        // ── سطور الفترة ────────────────────────────────────────────────
        $lines = collect();

        $this->customerSales($customerId)
            ->when($from, fn (Builder $q) => $q->whereDate('invoice_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('invoice_date', '<=', $to))
            ->orderBy('invoice_date')
            ->get()
            ->each(fn ($inv) => $lines->push((object) [
                'date' => $inv->invoice_date,
                'reference' => $inv->reference_number,
                'description' => 'فاتورة مبيعات',
                'debit' => (float) $inv->total_amount,
                'credit' => 0.0,
            ]));

        $this->customerReturns($customerId)
            ->when($from, fn (Builder $q) => $q->whereDate('invoice_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('invoice_date', '<=', $to))
            ->orderBy('invoice_date')
            ->get()
            ->each(fn ($ret) => $lines->push((object) [
                'date' => $ret->invoice_date,
                'reference' => $ret->reference_number,
                'description' => 'مرتجع مبيعات',
                'debit' => 0.0,
                'credit' => (float) $ret->total_amount,
            ]));

        $this->customerReceipts($customerId)
            ->when($from, fn (Builder $q) => $q->whereDate('receipt_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('receipt_date', '<=', $to))
            ->orderBy('receipt_date')
            ->get()
            ->each(fn ($rec) => $lines->push((object) [
                'date' => $rec->receipt_date,
                'reference' => $rec->receipt_number,
                'description' => 'سند قبض ('.PaymentMethod::labelFor($rec->payment_method).')',
                'debit' => 0.0,
                'credit' => (float) $rec->amount,
            ]));

        return $this->assembleStatement($opening, $lines, debitNormal: true);
    }

    /**
     * المستحق الائتماني الحالي للعميل (للتحقق من حد الائتمان).
     * = مجموع غير المسدّد من الفواتير الآجلة المؤكدة.
     *
     * مفهوم مختلف عن الرصيد: يخص البيع الآجل فقط، لا كل الذمّة.
     */
    public function customerCreditExposure(int $customerId): float
    {
        return (float) (Invoice::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', ['confirmed', 'delivered', 'partially_paid'])
            ->where('payment_type', 'credit')
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding')
            ->value('outstanding') ?? 0);
    }

    // ════════════════════════════════════════════════════════════════════
    //  الموردون
    // ════════════════════════════════════════════════════════════════════

    /**
     * الرصيد الحالي للمورد (كل الحركات، بدون فلتر تاريخ).
     * موجب = دائن (مستحق للمورد).
     */
    public function supplierBalance(int $supplierId): float
    {
        $opening = (float) (Supplier::whereKey($supplierId)->value('opening_balance') ?? 0);
        $purchases = (float) $this->supplierPurchases($supplierId)->sum('total_amount');
        $returns = (float) $this->supplierReturns($supplierId)->sum('total_amount');
        $payments = (float) $this->supplierPayments($supplierId)->sum('amount');

        return round($opening + $purchases - $returns - $payments, 2);
    }

    /**
     * كشف حساب المورد خلال فترة.
     *
     * @return array{opening: float, lines: Collection, totalDebit: float, totalCredit: float, closing: float}
     */
    public function supplierStatement(int $supplierId, ?string $from = null, ?string $to = null): array
    {
        // ── رصيد أول المدة ──────────────────────────────────────────────
        $opening = (float) (Supplier::whereKey($supplierId)->value('opening_balance') ?? 0);

        if ($from) {
            $opening += (float) $this->supplierPurchases($supplierId)->whereDate('invoice_date', '<', $from)->sum('total_amount');
            $opening -= (float) $this->supplierReturns($supplierId)->whereDate('return_date', '<', $from)->sum('total_amount');
            $opening -= (float) $this->supplierPayments($supplierId)->whereDate('payment_date', '<', $from)->sum('amount');
        }

        // ── سطور الفترة ────────────────────────────────────────────────
        $lines = collect();

        $this->supplierPurchases($supplierId)
            ->when($from, fn (Builder $q) => $q->whereDate('invoice_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('invoice_date', '<=', $to))
            ->orderBy('invoice_date')
            ->get()
            ->each(fn ($inv) => $lines->push((object) [
                'date' => $inv->invoice_date,
                'reference' => $inv->invoice_number ?? $inv->reference_number,
                'description' => 'فاتورة مشتريات',
                'debit' => 0.0,
                'credit' => (float) $inv->total_amount,
            ]));

        $this->supplierReturns($supplierId)
            ->when($from, fn (Builder $q) => $q->whereDate('return_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('return_date', '<=', $to))
            ->orderBy('return_date')
            ->get()
            ->each(fn ($ret) => $lines->push((object) [
                'date' => $ret->return_date,
                'reference' => $ret->reference_number,
                'description' => 'مرتجع مشتريات',
                'debit' => (float) $ret->total_amount,
                'credit' => 0.0,
            ]));

        $this->supplierPayments($supplierId)
            ->when($from, fn (Builder $q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('payment_date', '<=', $to))
            ->orderBy('payment_date')
            ->get()
            ->each(fn ($pay) => $lines->push((object) [
                'date' => $pay->payment_date,
                'reference' => $pay->payment_number,
                'description' => 'سند صرف ('.PaymentMethod::labelFor($pay->payment_method).')',
                'debit' => (float) $pay->amount,
                'credit' => 0.0,
            ]));

        return $this->assembleStatement($opening, $lines, debitNormal: false);
    }

    // ════════════════════════════════════════════════════════════════════
    //  Private — تعريف القواعد مرة واحدة
    // ════════════════════════════════════════════════════════════════════

    private function customerSales(int $customerId): Builder
    {
        return Invoice::query()
            ->where('customer_id', $customerId)
            ->where('type', 'sale')
            ->whereNotIn('status', ['draft', 'cancelled']);
    }

    private function customerReturns(int $customerId): Builder
    {
        return Invoice::query()
            ->where('customer_id', $customerId)
            ->where('type', 'sale_return')
            ->whereNotIn('status', ['draft', 'cancelled']);
    }

    private function customerReceipts(int $customerId): Builder
    {
        return Receipt::query()->where('customer_id', $customerId);
    }

    private function supplierPurchases(int $supplierId): Builder
    {
        return PurchaseInvoice::query()
            ->where('supplier_id', $supplierId)
            ->whereIn('status', ['confirmed', 'paid']);
    }

    private function supplierReturns(int $supplierId): Builder
    {
        return PurchaseReturn::query()
            ->where('supplier_id', $supplierId)
            ->where('status', 'confirmed');
    }

    private function supplierPayments(int $supplierId): Builder
    {
        return Payment::query()
            ->where('supplier_id', $supplierId)
            ->where('category', 'supplier_payment');
    }

    /**
     * يرتّب السطور ويحسب الإجماليات والرصيد الختامي.
     *
     * @param  bool  $debitNormal  true للعميل (مدين)، false للمورد (دائن)
     * @return array{opening: float, lines: Collection, totalDebit: float, totalCredit: float, closing: float}
     */
    private function assembleStatement(float $opening, Collection $lines, bool $debitNormal): array
    {
        $lines = $lines->sortBy('date')->values();
        $totalDebit = (float) $lines->sum('debit');
        $totalCredit = (float) $lines->sum('credit');

        $closing = $debitNormal
            ? $opening + $totalDebit - $totalCredit
            : $opening + $totalCredit - $totalDebit;

        return [
            'opening' => round($opening, 2),
            'lines' => $lines,
            'totalDebit' => round($totalDebit, 2),
            'totalCredit' => round($totalCredit, 2),
            'closing' => round($closing, 2),
        ];
    }
}
