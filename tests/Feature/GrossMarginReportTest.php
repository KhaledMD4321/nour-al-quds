<?php

namespace Tests\Feature;

use App\Modules\Reports\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * يثبّت حساب هامش الربح: الإيراد (من بنود الفواتير) − التكلفة
 * (حركات الصادر × متوسط التكلفة)، وتجميعه بالمصنّع.
 */
class GrossMarginReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_gross_margin_by_product_then_manufacturer(): void
    {
        $bu = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $wh = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $bu]);
        $customer = DB::table('customers')->insertGetId(['code' => 'CUS-G1', 'name' => 'عميل']);
        $company = DB::table('companies')->insertGetId(['name' => 'نصار للمواسير']);
        $product = DB::table('products')->insertGetId([
            'code' => 'PRD-G1', 'name' => 'كوع نحاس', 'unit_of_measure' => 'piece', 'company_id' => $company,
        ]);

        // فاتورة بيع: إيراد 1000 من 10 وحدات
        $invoice = DB::table('invoices')->insertGetId([
            'reference_number' => 'INV-G1', 'business_unit_id' => $bu, 'warehouse_id' => $wh, 'customer_id' => $customer,
            'type' => 'sale', 'status' => 'confirmed', 'payment_type' => 'cash',
            'total_amount' => 1000, 'paid_amount' => 0, 'invoice_date' => '2026-05-10',
        ]);
        DB::table('invoice_items')->insert([
            'invoice_id' => $invoice, 'product_id' => $product,
            'quantity' => 10, 'unit_price' => 100, 'total' => 1000,
        ]);

        // متوسط تكلفة الصنف = 60 → تكلفة المبيعات = 10 × 60 = 600
        DB::table('stock')->insert([
            'warehouse_id' => $wh, 'product_id' => $product, 'quantity' => 100, 'avg_cost' => 60,
        ]);
        DB::table('stock_movements')->insert([
            'warehouse_id' => $wh, 'product_id' => $product, 'type' => 'out', 'quantity' => 10,
            'balance_after' => 90, 'reference_type' => 'invoice', 'reference_id' => $invoice,
            'created_at' => '2026-05-10 10:00:00',
        ]);

        $svc = app(ReportService::class);

        // ── بالصنف ──
        $byProduct = $svc->grossMarginByProduct('2026-05-01', '2026-05-31', null);
        $this->assertCount(1, $byProduct);
        $row = $byProduct->first();
        $this->assertEqualsWithDelta(1000.0, $row->revenue, 0.001);
        $this->assertEqualsWithDelta(600.0, $row->cogs, 0.001);
        $this->assertEqualsWithDelta(400.0, $row->gross_profit, 0.001);
        $this->assertEqualsWithDelta(40.0, $row->margin_pct, 0.001);
        $this->assertSame('نصار للمواسير', $row->manufacturer);

        // ── بالمصنّع ──
        $byMfr = $svc->grossMarginByManufacturer('2026-05-01', '2026-05-31', null);
        $this->assertCount(1, $byMfr);
        $this->assertSame('نصار للمواسير', $byMfr->first()->manufacturer);
        $this->assertSame(1, $byMfr->first()->products);
        $this->assertEqualsWithDelta(400.0, $byMfr->first()->gross_profit, 0.001);
    }
}
