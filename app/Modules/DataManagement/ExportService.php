<?php

namespace App\Modules\DataManagement;

use App\Models\BusinessUnit;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Receipt;
use App\Models\Payment;
use App\Models\Cheque;
use App\Models\Stock;
use App\Models\TreasuryTransaction;
use App\Modules\Reports\ReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExportService
{
    public function __construct(protected ReportService $reports) {}

    /**
     * تصدير بيانات إلى Excel
     * @return string  path to temp file
     */
    public function exportToExcel(string $dataType, array $filters = []): string
    {
        $data     = $this->getData($dataType, $filters);
        $headers  = $this->getHeaders($dataType);
        $fileName = 'export_' . $dataType . '_' . now()->format('Ymd_His') . '.xlsx';
        $path     = 'exports/' . $fileName;

        Excel::store(
            new GenericExport($data, $headers),
            $path,
            'local'
        );

        Log::info('Data exported', [
            'type'    => $dataType,
            'rows'    => $data->count(),
            'user_id' => auth()->id(),
            'path'    => $path,
        ]);

        return storage_path('app/' . $path);
    }

    /**
     * الحصول على البيانات بحسب النوع
     */
    public function getData(string $dataType, array $filters = []): Collection
    {
        $buId = $filters['business_unit_id'] ?? null;
        $from = $filters['from_date'] ?? null;
        $to   = $filters['to_date']   ?? null;

        return match($dataType) {
            'customers' => $this->exportCustomers($buId),
            'suppliers' => $this->exportSuppliers(),
            'products'  => $this->exportProducts(),
            'stock'     => $this->exportStock($buId),
            'invoices'  => $this->exportInvoices($from, $to, $buId),
            'purchases' => $this->exportPurchases($from, $to, $buId),
            'receipts'  => $this->exportReceipts($from, $to, $buId),
            'payments'  => $this->exportPayments($from, $to, $buId),
            'cheques'   => $this->exportCheques($filters['direction'] ?? null),
            'aging_customers' => $this->reports->customerAging($buId, $to)->map(fn ($r) => collect($r)->toArray()),
            'aging_suppliers' => $this->reports->supplierAging($buId, $to)->map(fn ($r) => collect($r)->toArray()),
            default     => collect(),
        };
    }

    public function getHeaders(string $dataType): array
    {
        return match($dataType) {
            'customers'       => ['الكود', 'الاسم', 'التليفون', 'العنوان', 'النوع', 'حد الائتمان', 'الرصيد الافتتاحي'],
            'suppliers'       => ['الكود', 'الاسم', 'التليفون', 'العنوان', 'الرصيد الافتتاحي'],
            'products'        => ['الكود', 'الاسم', 'الاسم بالإنجليزي', 'المصنّع', 'وحدة القياس', 'الحد الأدنى'],
            'stock'           => ['المخزن', 'كود الصنف', 'الصنف', 'الكمية', 'متوسط التكلفة', 'القيمة الإجمالية'],
            'invoices'        => ['رقم الفاتورة', 'تاريخ الفاتورة', 'العميل', 'الإجمالي', 'المحصّل', 'المتبقي', 'الحالة'],
            'purchases'       => ['رقم الفاتورة', 'تاريخ الفاتورة', 'المورد', 'الإجمالي', 'المسدّد', 'المستحق', 'الحالة'],
            'receipts'        => ['رقم السند', 'التاريخ', 'العميل', 'المبلغ', 'طريقة الدفع', 'الخزينة'],
            'payments'        => ['رقم السند', 'التاريخ', 'المورد', 'المبلغ', 'طريقة الدفع', 'التصنيف'],
            'cheques'         => ['رقم الشيك', 'البنك', 'المبلغ', 'تاريخ الإصدار', 'تاريخ الاستحقاق', 'الاتجاه', 'الحالة'],
            'aging_customers' => ['الكود', 'العميل', 'جاري', '1-30 يوم', '31-60 يوم', '61-90 يوم', '+90 يوم', 'الإجمالي'],
            'aging_suppliers' => ['الكود', 'المورد', 'جاري', '1-30 يوم', '31-60 يوم', '61-90 يوم', '+90 يوم', 'الإجمالي'],
            default           => [],
        };
    }

    // ── private methods ────────────────────────────────────────────────

    private function exportCustomers(?int $buId): Collection
    {
        $q = Customer::orderBy('code');
        if ($buId) $q->where('business_unit_id', $buId);

        return $q->get()->map(fn ($c) => [
            $c->code, $c->name, $c->phone, $c->address,
            $c->type, $c->credit_limit, $c->opening_balance,
        ]);
    }

    private function exportSuppliers(): Collection
    {
        return Supplier::orderBy('code')->get()->map(fn ($s) => [
            $s->code, $s->name, $s->phone, $s->address, $s->opening_balance,
        ]);
    }

    private function exportProducts(): Collection
    {
        return Product::with('company')->orderBy('code')->get()->map(fn ($p) => [
            $p->code, $p->name, $p->name_en, $p->company?->name, $p->unit_of_measure, $p->min_stock_level,
        ]);
    }

    private function exportStock(?int $buId): Collection
    {
        $q = Stock::with(['product', 'warehouse'])->where('quantity', '>', 0);
        if ($buId) $q->whereHas('warehouse', fn ($wq) => $wq->where('business_unit_id', $buId));

        return $q->get()->map(fn ($s) => [
            $s->warehouse->name, $s->product->code, $s->product->name,
            $s->quantity, $s->avg_cost, round($s->quantity * $s->avg_cost, 2),
        ]);
    }

    private function exportInvoices(?string $from, ?string $to, ?int $buId): Collection
    {
        $q = Invoice::with('customer')->where('type', 'sale');
        if ($from) $q->whereDate('invoice_date', '>=', $from);
        if ($to)   $q->whereDate('invoice_date', '<=', $to);
        if ($buId) $q->where('business_unit_id', $buId);

        return $q->orderBy('invoice_date')->get()->map(fn ($i) => [
            $i->reference_number, $i->invoice_date->format('Y-m-d'),
            $i->customer?->name, $i->total_amount, $i->paid_amount,
            $i->total_amount - $i->paid_amount, $i->status,
        ]);
    }

    private function exportPurchases(?string $from, ?string $to, ?int $buId): Collection
    {
        $q = PurchaseInvoice::with('supplier');
        if ($from) $q->whereDate('invoice_date', '>=', $from);
        if ($to)   $q->whereDate('invoice_date', '<=', $to);
        if ($buId) $q->where('business_unit_id', $buId);

        return $q->orderBy('invoice_date')->get()->map(fn ($i) => [
            $i->invoice_number, $i->invoice_date->format('Y-m-d'),
            $i->supplier?->name, $i->total_amount, $i->paid_amount,
            $i->total_amount - $i->paid_amount, $i->status,
        ]);
    }

    private function exportReceipts(?string $from, ?string $to, ?int $buId): Collection
    {
        $q = Receipt::with(['customer', 'treasury']);
        if ($from) $q->whereDate('receipt_date', '>=', $from);
        if ($to)   $q->whereDate('receipt_date', '<=', $to);
        if ($buId) $q->where('business_unit_id', $buId);

        return $q->orderBy('receipt_date')->get()->map(fn ($r) => [
            $r->receipt_number, $r->receipt_date->format('Y-m-d'),
            $r->customer?->name, $r->amount, $r->payment_method, $r->treasury?->name,
        ]);
    }

    private function exportPayments(?string $from, ?string $to, ?int $buId): Collection
    {
        $q = Payment::with(['supplier', 'treasury']);
        if ($from) $q->whereDate('payment_date', '>=', $from);
        if ($to)   $q->whereDate('payment_date', '<=', $to);
        if ($buId) $q->where('business_unit_id', $buId);

        return $q->orderBy('payment_date')->get()->map(fn ($p) => [
            $p->payment_number, $p->payment_date->format('Y-m-d'),
            $p->supplier?->name, $p->amount, $p->payment_method, $p->category,
        ]);
    }

    private function exportCheques(?string $direction): Collection
    {
        $q = Cheque::orderBy('due_date');
        if ($direction) $q->where('direction', $direction);

        return $q->get()->map(fn ($c) => [
            $c->cheque_number, $c->bank_name, $c->amount,
            $c->issue_date->format('Y-m-d'), $c->due_date->format('Y-m-d'),
            $c->direction === 'incoming' ? 'وارد' : 'صادر',
            $c->status_label,
        ]);
    }
}

// ── Inline Excel Export class ──────────────────────────────────────────────────

class GenericExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private Collection $data,
        private array      $headers
    ) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
