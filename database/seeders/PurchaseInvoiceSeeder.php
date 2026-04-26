<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\LandedCost;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PurchaseInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $admin     = User::first();
        $supplier1 = Supplier::first();
        $supplier2 = Supplier::skip(1)->first();
        $warehouse = Warehouse::first();
        $bu        = BusinessUnit::first();

        $products = Product::orderBy('id')->take(5)->get();

        // ── فاتورة 1: مسودة بدون مصاريف إضافية ──────────────────────────────
        $inv1 = PurchaseInvoice::create([
            'reference_number' => PurchaseInvoice::generateReference(),
            'supplier_id'      => $supplier1->id,
            'warehouse_id'     => $warehouse->id,
            'business_unit_id' => $bu->id,
            'invoice_number'   => 'INV-2026-001',
            'invoice_date'     => '2026-01-15',
            'due_date'         => '2026-02-15',
            'status'           => 'draft',
            'notes'            => 'فاتورة تجريبية — مسودة',
            'created_by'       => $admin->id,
        ]);

        $items1 = [
            ['product' => $products[0], 'qty' => 10, 'cost' => 750],
            ['product' => $products[1], 'qty' => 5,  'cost' => 1200],
            ['product' => $products[2], 'qty' => 15, 'cost' => 380],
        ];

        $subtotal1 = 0;
        foreach ($items1 as $item) {
            $total = round($item['qty'] * $item['cost'], 2);
            $subtotal1 += $total;
            PurchaseInvoiceItem::create([
                'purchase_invoice_id' => $inv1->id,
                'product_id'          => $item['product']->id,
                'quantity'            => $item['qty'],
                'unit_cost'           => $item['cost'],
                'total'               => $total,
            ]);
        }

        $inv1->update([
            'subtotal'     => $subtotal1,
            'total_amount' => $subtotal1,
        ]);

        // ── فاتورة 2: مسودة مع مصاريف شحن ───────────────────────────────────
        $inv2 = PurchaseInvoice::create([
            'reference_number' => PurchaseInvoice::generateReference(),
            'supplier_id'      => $supplier2->id,
            'warehouse_id'     => $warehouse->id,
            'business_unit_id' => $bu->id,
            'invoice_number'   => 'INV-2026-002',
            'invoice_date'     => '2026-01-20',
            'due_date'         => '2026-02-20',
            'status'           => 'draft',
            'notes'            => 'فاتورة تجريبية مع مصاريف شحن',
            'created_by'       => $admin->id,
        ]);

        $items2 = [
            ['product' => $products[2], 'qty' => 20, 'cost' => 380],
            ['product' => $products[3], 'qty' => 8,  'cost' => 550],
            ['product' => $products[4], 'qty' => 12, 'cost' => 290],
        ];

        $subtotal2 = 0;
        foreach ($items2 as $item) {
            $total = round($item['qty'] * $item['cost'], 2);
            $subtotal2 += $total;
            PurchaseInvoiceItem::create([
                'purchase_invoice_id' => $inv2->id,
                'product_id'          => $item['product']->id,
                'quantity'            => $item['qty'],
                'unit_cost'           => $item['cost'],
                'total'               => $total,
            ]);
        }

        // إضافة مصروف شحن
        LandedCost::create([
            'purchase_invoice_id' => $inv2->id,
            'cost_type'           => 'transport',
            'amount'              => 200,
            'description'         => 'مصاريف شحن من المورد',
        ]);

        // إضافة مصروف تأمين
        LandedCost::create([
            'purchase_invoice_id' => $inv2->id,
            'cost_type'           => 'insurance',
            'amount'              => 80,
            'description'         => 'تأمين على البضاعة',
        ]);

        $totalLanded2 = 280;
        $inv2->update([
            'subtotal'          => $subtotal2,
            'total_landed_cost' => $totalLanded2,
            'total_amount'      => $subtotal2 + $totalLanded2,
        ]);

        $this->command->info('✅ تم إنشاء فاتورتين تجريبيتين:');
        $this->command->info("   {$inv1->reference_number} — إجمالي " . number_format($inv1->total_amount, 2) . ' ج.م.');
        $this->command->info("   {$inv2->reference_number} — إجمالي " . number_format($inv2->total_amount, 2) . ' ج.م. (يشمل 280 ج.م. مصاريف)');
    }
}
