<?php

namespace App\Livewire;

use App\Models\BusinessUnit;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Modules\Sales\QuickSaleService;
use Exception;
use Livewire\Component;

class QuickSaleForm extends Component
{
    // ── إعدادات الجلسة ──────────────────────────────────────────────────────
    public int $businessUnitId = 0;

    public int $warehouseId = 0;

    public string $customerName = '';

    public string $notes = '';

    // ── البحث ───────────────────────────────────────────────────────────────
    public string $searchQuery = '';

    public array $searchResults = [];

    // ── السلة ───────────────────────────────────────────────────────────────
    // كل item: ['product_id', 'name', 'quantity', 'unit_price', 'total', 'available']
    public array $items = [];

    // ── الإجمالي ────────────────────────────────────────────────────────────
    public float $totalAmount = 0;

    // ── حالة الـ UI ─────────────────────────────────────────────────────────
    public bool $saleCompleted = false;

    public ?int $lastSaleId = null;

    public string $errorMessage = '';

    // ── Validation ──────────────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'businessUnitId' => 'required|exists:business_units,id',
            'warehouseId' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'items.required' => 'لازم تضيف صنف واحد على الأقل',
            'items.min' => 'لازم تضيف صنف واحد على الأقل',
        ];
    }

    // ── Mount ────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $unit = BusinessUnit::first();
        if ($unit) {
            $this->businessUnitId = $unit->id;
            $warehouse = Warehouse::where('business_unit_id', $unit->id)->first()
                      ?? Warehouse::first();
            if ($warehouse) {
                $this->warehouseId = $warehouse->id;
            }
        }
    }

    // ── البحث عن الأصناف ────────────────────────────────────────────────────
    public function updatedSearchQuery(): void
    {
        $this->errorMessage = '';

        if (mb_strlen($this->searchQuery) < 2) {
            $this->searchResults = [];

            return;
        }

        $warehouseId = $this->warehouseId ?: (Warehouse::first()?->id ?? 0);

        $this->searchResults = Product::where('name', 'ilike', '%'.$this->searchQuery.'%')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'code', 'company_id'])
            ->map(function (Product $product) use ($warehouseId) {
                $price = $this->resolvePrice($product, $warehouseId);
                $stock = Stock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)
                    ->first();
                $available = $stock ? (float) $stock->quantity : 0;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code ?? '',
                    'price' => $price,
                    'available' => $available,
                ];
            })
            ->toArray();
    }

    // ── إضافة صنف للسلة ─────────────────────────────────────────────────────
    public function addProduct(int $productId): void
    {
        $this->errorMessage = '';

        // لو الصنف موجود بالفعل — زوّد الكمية
        foreach ($this->items as $index => $item) {
            if ($item['product_id'] === $productId) {
                $this->increaseQuantity($index);
                $this->searchQuery = '';
                $this->searchResults = [];

                return;
            }
        }

        // صنف جديد
        $result = collect($this->searchResults)->firstWhere('id', $productId);
        if (! $result) {
            return;
        }

        $this->items[] = [
            'product_id' => $productId,
            'name' => $result['name'],
            'quantity' => 1,
            'unit_price' => $result['price'],
            'total' => $result['price'],
            'available' => $result['available'],
        ];

        $this->recalcTotal();
        $this->searchQuery = '';
        $this->searchResults = [];
    }

    // ── تعديل الكمية ────────────────────────────────────────────────────────
    public function increaseQuantity(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }
        $this->items[$index]['quantity']++;
        $this->recalcItem($index);
    }

    public function decreaseQuantity(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }
        if ($this->items[$index]['quantity'] <= 1) {
            $this->removeItem($index);

            return;
        }
        $this->items[$index]['quantity']--;
        $this->recalcItem($index);
    }

    // ── تحديث عند تعديل السعر/الكمية يدوياً ────────────────────────────────
    public function updatedItems(): void
    {
        foreach (array_keys($this->items) as $index) {
            $this->recalcItem($index);
        }
        $this->recalcTotal();
    }

    // ── حذف بند ─────────────────────────────────────────────────────────────
    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
        $this->recalcTotal();
    }

    // ── تنفيذ البيع ─────────────────────────────────────────────────────────
    public function processSale(): void
    {
        $this->errorMessage = '';
        $this->validate();

        try {
            $sale = app(QuickSaleService::class)->process([
                'business_unit_id' => $this->businessUnitId,
                'warehouse_id' => $this->warehouseId,
                'customer_name' => $this->customerName ?: null,
                'notes' => $this->notes ?: null,
                'items' => collect($this->items)->map(fn ($item) => [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ])->toArray(),
            ]);

            $this->lastSaleId = $sale->id;
            $this->saleCompleted = true;
            $this->items = [];
            $this->totalAmount = 0;
            $this->customerName = '';
            $this->notes = '';

        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // ── طباعة الإيصال ───────────────────────────────────────────────────────
    public function printReceipt(): void
    {
        if ($this->lastSaleId) {
            $this->redirect(route('quick-sale.receipt', $this->lastSaleId));
        }
    }

    // ── بيع جديد ────────────────────────────────────────────────────────────
    public function newSale(): void
    {
        $this->saleCompleted = false;
        $this->lastSaleId = null;
        $this->errorMessage = '';
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * ترتيب أولوية السعر:
     * 1. قائمة أسعار نشطة للشركة المصنّعة
     * 2. أي قائمة أسعار (بغض النظر عن الحالة) — آخر إصدار
     * 3. متوسط تكلفة من المخزن (avg_cost) — كمقترح للكاشير
     * 4. صفر
     */
    private function resolvePrice(Product $product, int $warehouseId): float
    {
        // 1. السعر من القائمة النشطة
        $price = $product->getCurrentPrice();
        if ($price !== null) {
            return $price;
        }

        // 2. آخر سعر معروف من أي إصدار
        $anyPrice = PriceListItem::where('product_id', $product->id)
            ->whereHas('version')
            ->orderByDesc('id')
            ->value('price');
        if ($anyPrice !== null) {
            return (float) $anyPrice;
        }

        // 3. متوسط التكلفة من المخزن كمقترح
        $avgCost = Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $product->id)
            ->value('avg_cost');
        if ($avgCost !== null) {
            return (float) $avgCost;
        }

        return 0;
    }

    private function recalcItem(int $index): void
    {
        $item = &$this->items[$index];
        $item['total'] = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
        $this->recalcTotal();
    }

    private function recalcTotal(): void
    {
        $this->totalAmount = round(
            collect($this->items)->sum(fn ($i) => (float) $i['total']),
            2
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.quick-sale-form');
    }
}
