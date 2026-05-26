<?php

namespace Tests\Feature;

use App\Modules\Reports\ProductInsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductInsightsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductInsightsService $svc;

    private int $buId;

    private int $whId;

    private int $customerId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(ProductInsightsService::class);
        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
        $this->customerId = DB::table('customers')->insertGetId(['code' => 'CUS-PI', 'name' => 'عميل']);
    }

    public function test_abc_classifies_products_by_cumulative_revenue(): void
    {
        $inv = $this->saleInvoice('2026-05-10');
        $a = $this->product();
        $b = $this->product();
        $c = $this->product();
        $this->item($inv, $a, 10, 800);  // 80% → A
        $this->item($inv, $b, 5, 150);   // 95% → B
        $this->item($inv, $c, 2, 50);    // 100% → C

        $rows = $this->svc->abcAnalysis('2026-05-01', '2026-05-31', null);

        $this->assertCount(3, $rows);
        $this->assertSame('A', $rows->firstWhere('revenue', 800.0)->class);
        $this->assertSame('B', $rows->firstWhere('revenue', 150.0)->class);
        $this->assertSame('C', $rows->firstWhere('revenue', 50.0)->class);
    }

    public function test_reorder_suggests_only_items_at_or_below_minimum(): void
    {
        $low = $this->product(min: 20);
        $this->stock($low, 10);                          // ≤ الحد الأدنى
        $this->movement($low, 60, now()->toDateTimeString());  // باع 60 خلال 30 يوم → سرعة 2/يوم

        $ok = $this->product(min: 20);
        $this->stock($ok, 100);                          // فوق الحد → مستبعد

        $noPoint = $this->product(min: 0);
        $this->stock($noPoint, 5);                       // بلا حدّ أدنى → مستبعد

        $rows = $this->svc->reorderSuggestions(30, null);

        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertEqualsWithDelta(2.0, $row->velocity, 0.001);
        $this->assertSame(5, $row->days_cover);          // floor(10 / 2)
        $this->assertSame(50, $row->suggested_order);    // max(60, 20) − 10
    }

    private function product(float $min = 0): int
    {
        return DB::table('products')->insertGetId([
            'code' => 'PRD-PI'.(++$this->n),
            'name' => 'صنف',
            'unit_of_measure' => 'piece',
            'min_stock_level' => $min,
        ]);
    }

    private function saleInvoice(string $date): int
    {
        return DB::table('invoices')->insertGetId([
            'reference_number' => 'INV-PI'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->whId,
            'customer_id' => $this->customerId,
            'type' => 'sale',
            'status' => 'confirmed',
            'payment_type' => 'cash',
            'total_amount' => 0,
            'paid_amount' => 0,
            'invoice_date' => $date,
        ]);
    }

    private function item(int $invoiceId, int $productId, float $qty, float $total): void
    {
        DB::table('invoice_items')->insert([
            'invoice_id' => $invoiceId,
            'product_id' => $productId,
            'quantity' => $qty,
            'unit_price' => $total / max($qty, 1),
            'total' => $total,
        ]);
    }

    private function stock(int $productId, float $qty): void
    {
        DB::table('stock')->insert([
            'warehouse_id' => $this->whId,
            'product_id' => $productId,
            'quantity' => $qty,
            'avg_cost' => 5,
        ]);
    }

    private function movement(int $productId, float $qty, string $at): void
    {
        DB::table('stock_movements')->insert([
            'warehouse_id' => $this->whId,
            'product_id' => $productId,
            'type' => 'out',
            'quantity' => $qty,
            'balance_after' => 0,
            'reference_type' => 'invoice',
            'reference_id' => 1,
            'created_at' => $at,
        ]);
    }
}
