<?php

namespace App\Livewire;

use App\Models\BusinessUnit;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PriceListItem;
use App\Models\PriceListVersion;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Modules\Sales\InvoiceService;
use App\Modules\Sales\PriceCalculator;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InvoiceBuilder extends Component
{
    // ── Header ───────────────────────────────────────────────────────────────────
    public int    $customerId     = 0;
    public int    $warehouseId    = 0;
    public int    $businessUnitId = 0;
    public string $invoiceDate    = '';
    public string $dueDate        = '';
    public string $paymentType    = 'cash';
    public string $notes          = '';

    // ── خصومات افتراضية من العميل (قابلة للتعديل يدوياً) ────────────────────────
    public float $defaultD1 = 0;
    public float $defaultD2 = 0;
    public float $defaultD3 = 0;

    // ── فلترة الأصناف ───────────────────────────────────────────────────────────
    public int    $selectedCompanyId = 0;
    public string $searchQuery       = '';
    public array  $productList       = [];

    // ── بنود الفاتورة (السلة) ────────────────────────────────────────────────────
    // كل item: product_id, name, list_price, d1, d2, d3, unit_price, quantity, total, available
    public array $items = [];

    // ── الإجماليات ───────────────────────────────────────────────────────────────
    public float $subtotal       = 0;
    public float $discountAmount = 0;
    public float $totalAmount    = 0;

    // ── UI State ─────────────────────────────────────────────────────────────────
    public string $successMessage = '';
    public string $errorMessage   = '';
    public ?int   $savedInvoiceId = null;

    // ── Mount ─────────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->invoiceDate = now()->format('Y-m-d');

        $unit = BusinessUnit::first();
        if ($unit) {
            $this->businessUnitId = $unit->id;
            $wh = Warehouse::where('business_unit_id', $unit->id)
                ->where('is_active', true)
                ->first()
                ?? Warehouse::where('is_active', true)->first();
            if ($wh) {
                $this->warehouseId = $wh->id;
            }
        }
    }

    // ── اختيار العميل — جلب الخصومات الافتراضية ────────────────────────────────
    public function updatedCustomerId(): void
    {
        $this->errorMessage = '';
        if (! $this->customerId) return;

        $customer = Customer::find($this->customerId);
        if (! $customer) return;

        $this->defaultD1 = (float) $customer->default_discount_1;
        $this->defaultD2 = (float) $customer->default_discount_2;
        $this->defaultD3 = (float) $customer->default_discount_3;

        // تحديث خصومات البنود الموجودة
        foreach ($this->items as $i => $_) {
            $this->items[$i]['d1'] = $this->defaultD1;
            $this->items[$i]['d2'] = $this->defaultD2;
            $this->items[$i]['d3'] = $this->defaultD3;
            $this->recalcItem($i);
        }
        $this->recalcTotals();
    }

    // ── تغيير الخصم الافتراضي يدوياً — تطبيق على كل البنود ─────────────────────
    public function updatedDefaultD1(): void { $this->applyDefaultDiscounts(); }
    public function updatedDefaultD2(): void { $this->applyDefaultDiscounts(); }
    public function updatedDefaultD3(): void { $this->applyDefaultDiscounts(); }

    private function applyDefaultDiscounts(): void
    {
        foreach ($this->items as $i => $_) {
            $this->items[$i]['d1'] = $this->defaultD1;
            $this->items[$i]['d2'] = $this->defaultD2;
            $this->items[$i]['d3'] = $this->defaultD3;
            $this->recalcItem($i);
        }
        $this->recalcTotals();
    }

    // ── فلتر المصنّع ──────────────────────────────────────────────────────────────
    public function updatedSelectedCompanyId(): void
    {
        $this->searchQuery = '';
        $this->loadProductList();
    }

    // ── البحث بالاسم ─────────────────────────────────────────────────────────────
    public function updatedSearchQuery(): void
    {
        $this->loadProductList();
    }

    // ── تحميل قائمة الأصناف ──────────────────────────────────────────────────────
    public function loadProductList(): void
    {
        if (! $this->selectedCompanyId && mb_strlen($this->searchQuery) < 2) {
            $this->productList = [];
            return;
        }

        $query = Product::query()->where('is_active', true);

        if ($this->selectedCompanyId) {
            $query->where('company_id', $this->selectedCompanyId);
        }

        if (mb_strlen($this->searchQuery) >= 2) {
            $query->where('name', 'ilike', '%' . $this->searchQuery . '%');
        }

        $warehouseId = $this->warehouseId;

        $this->productList = $query
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'code', 'company_id'])
            ->map(function (Product $product) use ($warehouseId) {
                $listPrice = $this->resolveListPrice($product);

                $available = $warehouseId
                    ? (float) (Stock::where('warehouse_id', $warehouseId)
                        ->where('product_id', $product->id)
                        ->value('quantity') ?? 0)
                    : 0;

                $cartItem = collect($this->items)->firstWhere('product_id', $product->id);

                return [
                    'id'        => $product->id,
                    'name'      => $product->name,
                    'code'      => $product->code ?? '',
                    'price'     => $listPrice,
                    'available' => $available,
                    'in_cart'   => (bool) $cartItem,
                    'cart_qty'  => $cartItem ? (float) $cartItem['quantity'] : 0,
                ];
            })
            ->toArray();
    }

    // ── إضافة صنف للفاتورة ───────────────────────────────────────────────────────
    public function addProduct(int $productId): void
    {
        $this->errorMessage = '';

        // لو موجود — زوّد الكمية
        foreach ($this->items as $i => $item) {
            if ($item['product_id'] === $productId) {
                $this->items[$i]['quantity'] = (float) $this->items[$i]['quantity'] + 1;
                $this->recalcItem($i);
                $this->recalcTotals();
                $this->refreshProductListStatus();
                return;
            }
        }

        // صنف جديد — ابحث في productList أولاً ثم fallback لـ DB
        $product = collect($this->productList)->firstWhere('id', $productId);

        if (! $product) {
            $p = Product::find($productId);
            if (! $p) return;
            $available = $this->warehouseId
                ? (float) (Stock::where('warehouse_id', $this->warehouseId)
                    ->where('product_id', $productId)
                    ->value('quantity') ?? 0)
                : 0;
            $product = [
                'id'        => $p->id,
                'name'      => $p->name,
                'price'     => $this->resolveListPrice($p),
                'available' => $available,
            ];
        }

        $listPrice = (float) $product['price'];
        $unitPrice = PriceCalculator::calculateUnitPrice(
            $listPrice,
            $this->defaultD1,
            $this->defaultD2,
            $this->defaultD3,
        );

        $this->items[] = [
            'product_id' => $productId,
            'name'       => $product['name'],
            'list_price' => $listPrice,
            'd1'         => $this->defaultD1,
            'd2'         => $this->defaultD2,
            'd3'         => $this->defaultD3,
            'unit_price' => $unitPrice,
            'quantity'   => 1,
            'total'      => round($unitPrice, 2),
            'available'  => (float) $product['available'],
        ];

        $this->recalcTotals();
        $this->refreshProductListStatus();
    }

    // ── حذف بند ──────────────────────────────────────────────────────────────────
    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
        $this->recalcTotals();
        $this->refreshProductListStatus();
    }

    // ── تحديث عند تعديل أي حقل في البند مباشرة ──────────────────────────────────
    public function updatedItems(): void
    {
        foreach (array_keys($this->items) as $i) {
            $this->recalcItem($i);
        }
        $this->recalcTotals();
        $this->refreshProductListStatus();
    }

    // ── حفظ مسودة ────────────────────────────────────────────────────────────────
    public function saveDraft(): void
    {
        $this->errorMessage = '';
        if (! $this->validateHeader()) return;
        if (empty($this->items)) {
            $this->errorMessage = 'لازم تضيف صنف واحد على الأقل';
            return;
        }

        try {
            $invoice             = $this->persistInvoice('draft');
            $this->savedInvoiceId = $invoice->id;
            $this->successMessage = 'تم حفظ المسودة — ' . $invoice->reference_number;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // ── تأكيد الفاتورة مباشرة ─────────────────────────────────────────────────────
    public function confirmInvoice(): void
    {
        $this->errorMessage = '';
        if (! $this->validateHeader()) return;
        if (empty($this->items)) {
            $this->errorMessage = 'لازم تضيف صنف واحد على الأقل';
            return;
        }

        try {
            $invoice = $this->persistInvoice('draft');
            app(InvoiceService::class)->confirmInvoice($invoice);
            $this->savedInvoiceId = $invoice->id;
            $this->successMessage = 'تم تأكيد الفاتورة — ' . $invoice->reference_number;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // ── طباعة PDF ─────────────────────────────────────────────────────────────────
    public function printPdf(): void
    {
        if ($this->savedInvoiceId) {
            $this->redirect(route('invoice.pdf', $this->savedInvoiceId));
        }
    }

    // ── فاتورة جديدة ─────────────────────────────────────────────────────────────
    public function resetForm(): void
    {
        $this->items            = [];
        $this->productList      = [];
        $this->searchQuery      = '';
        $this->selectedCompanyId = 0;
        $this->customerId       = 0;
        $this->notes            = '';
        $this->dueDate          = '';
        $this->defaultD1        = 0;
        $this->defaultD2        = 0;
        $this->defaultD3        = 0;
        $this->subtotal         = 0;
        $this->discountAmount   = 0;
        $this->totalAmount      = 0;
        $this->successMessage   = '';
        $this->errorMessage     = '';
        $this->savedInvoiceId   = null;
        $this->invoiceDate      = now()->format('Y-m-d');
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Private Helpers
    // ══════════════════════════════════════════════════════════════════════════════

    private function persistInvoice(string $status): Invoice
    {
        $invoice = Invoice::create([
            'reference_number' => Invoice::generateReference(),
            'business_unit_id' => $this->businessUnitId,
            'warehouse_id'     => $this->warehouseId,
            'customer_id'      => $this->customerId,
            'invoice_date'     => $this->invoiceDate,
            'due_date'         => $this->dueDate ?: null,
            'status'           => $status,
            'payment_type'     => $this->paymentType,
            'subtotal'         => $this->subtotal,
            'discount_amount'  => $this->discountAmount,
            'tax_amount'       => 0,
            'total_amount'     => $this->totalAmount,
            'paid_amount'      => 0,
            'notes'            => $this->notes ?: null,
            'created_by'       => Auth::id(),
        ]);

        foreach ($this->items as $item) {
            $invoice->items()->create([
                'product_id' => $item['product_id'],
                'list_price' => $item['list_price'],
                'discount_1' => $item['d1'],
                'discount_2' => $item['d2'],
                'discount_3' => $item['d3'],
                'unit_price' => $item['unit_price'],
                'quantity'   => $item['quantity'],
                'total'      => $item['total'],
            ]);
        }

        return $invoice;
    }

    /**
     * سعر اللستة بالأولوية:
     * 1. قائمة أسعار نشطة للمصنّع
     * 2. آخر سعر معروف من أي إصدار
     * 3. avg_cost من المخزن
     * 4. صفر
     */
    private function resolveListPrice(Product $product): float
    {
        if ($product->company_id) {
            $version = PriceListVersion::where('company_id', $product->company_id)
                ->where('status', 'active')
                ->latest('effective_date')
                ->first();

            if ($version) {
                $price = $version->getPriceFor($product->id);
                if ($price !== null) return (float) $price;
            }
        }

        // آخر سعر من أي إصدار
        $anyPrice = PriceListItem::where('product_id', $product->id)
            ->whereHas('version')
            ->orderByDesc('id')
            ->value('price');
        if ($anyPrice !== null) return (float) $anyPrice;

        // avg_cost كمقترح
        if ($this->warehouseId) {
            $avgCost = Stock::where('warehouse_id', $this->warehouseId)
                ->where('product_id', $product->id)
                ->value('avg_cost');
            if ($avgCost !== null) return (float) $avgCost;
        }

        return 0;
    }

    private function recalcItem(int $i): void
    {
        $item              = &$this->items[$i];
        $item['unit_price'] = PriceCalculator::calculateUnitPrice(
            (float) ($item['list_price'] ?? 0),
            (float) ($item['d1'] ?? 0),
            (float) ($item['d2'] ?? 0),
            (float) ($item['d3'] ?? 0),
        );
        $item['total'] = round((float) $item['unit_price'] * (float) ($item['quantity'] ?? 1), 2);
    }

    private function recalcTotals(): void
    {
        // subtotal = مجموع (list_price × quantity) قبل الخصم
        $this->subtotal = round(
            collect($this->items)->sum(fn ($i) => (float) $i['list_price'] * (float) $i['quantity']),
            2
        );
        // totalAmount = مجموع unit_price × quantity (بعد الخصومات)
        $this->totalAmount = round(
            collect($this->items)->sum(fn ($i) => (float) $i['total']),
            2
        );
        $this->discountAmount = round($this->subtotal - $this->totalAmount, 2);
    }

    private function refreshProductListStatus(): void
    {
        foreach ($this->productList as $i => $p) {
            $found = collect($this->items)->firstWhere('product_id', $p['id']);
            $this->productList[$i]['in_cart']  = (bool) $found;
            $this->productList[$i]['cart_qty'] = $found ? (float) $found['quantity'] : 0;
        }
    }

    private function validateHeader(): bool
    {
        if (! $this->customerId) {
            $this->errorMessage = 'اختر العميل أولاً';
            return false;
        }
        if (! $this->warehouseId) {
            $this->errorMessage = 'اختر المخزن';
            return false;
        }
        if (! $this->invoiceDate) {
            $this->errorMessage = 'حدد تاريخ الفاتورة';
            return false;
        }
        return true;
    }

    // ── Render ────────────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.invoice-builder', [
            'customers'     => Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'warehouses'    => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'businessUnits' => BusinessUnit::orderBy('name')->get(['id', 'name']),
            'companies'     => Company::orderBy('name')->get(['id', 'name']),
            'paymentTypes'  => ['cash' => 'نقدي', 'credit' => 'آجل', 'cheque' => 'شيك'],
        ]);
    }
}
