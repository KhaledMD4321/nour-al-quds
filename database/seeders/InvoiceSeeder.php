<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\Sales\InvoiceService;
use App\Modules\Sales\PriceCalculator;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $unit = BusinessUnit::first();
        $warehouse = Warehouse::first();
        $customer = Customer::first();

        if (! $unit || ! $warehouse || ! $customer) {
            $this->command->warn('InvoiceSeeder: تأكد من وجود وحدة تشغيلية ومخزن وعميل');

            return;
        }

        // ── فاتورة 1 — مسودة ─────────────────────────────────────────────────────
        $draft = Invoice::create([
            'reference_number' => Invoice::generateReference(),
            'business_unit_id' => $unit->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'created_by' => $user?->id,
            'status' => 'draft',
            'payment_type' => 'cash',
            'invoice_date' => today(),
        ]);

        $this->addItem($draft, $warehouse->id, 1, $customer, 2);
        $this->addItem($draft, $warehouse->id, 2, $customer, 1);

        app(InvoiceService::class)->recalculateTotals($draft->fresh());

        $this->command->info("✅ INV مسودة: {$draft->reference_number}");

        // ── فاتورة 2 — مؤكدة ─────────────────────────────────────────────────────
        $confirmed = Invoice::create([
            'reference_number' => Invoice::generateReference(),
            'business_unit_id' => $unit->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'created_by' => $user?->id,
            'status' => 'draft',
            'payment_type' => 'cash',
            'invoice_date' => today()->subDay(),
        ]);

        $this->addItem($confirmed, $warehouse->id, 3, $customer, 5);

        app(InvoiceService::class)->recalculateTotals($confirmed->fresh());

        // تأكيد الفاتورة (يخصم المخزون)
        app(InvoiceService::class)->confirmInvoice($confirmed->fresh());

        $this->command->info("✅ INV مؤكدة: {$confirmed->reference_number}");
    }

    // ── Helper ────────────────────────────────────────────────────────────────────

    private function addItem(Invoice $invoice, int $warehouseId, int $productId, Customer $customer, float $qty): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $listPrice = $product->getCurrentPrice() ?? 0;

        // fallback: أي سعر معروف
        if ($listPrice == 0) {
            $listPrice = (float) (PriceListItem::where('product_id', $productId)
                ->whereHas('version')
                ->orderByDesc('id')
                ->value('price') ?? 0);
        }

        // fallback: avg_cost
        if ($listPrice == 0) {
            $listPrice = (float) (Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->value('avg_cost') ?? 0);
        }

        $d1 = (float) $customer->default_discount_1;
        $d2 = (float) $customer->default_discount_2;
        $d3 = (float) $customer->default_discount_3;
        $unitPrice = PriceCalculator::calculateUnitPrice($listPrice, $d1, $d2, $d3);
        $total = round($qty * $unitPrice, 2);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $productId,
            'quantity' => $qty,
            'list_price' => $listPrice,
            'discount_1' => $d1,
            'discount_2' => $d2,
            'discount_3' => $d3,
            'unit_price' => $unitPrice,
            'total' => $total,
        ]);
    }
}
