<?php

namespace Tests\Feature;

use App\Modules\Reports\ReturnsAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReturnsAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReturnsAnalysisService $svc;

    private int $buId;

    private int $whId;

    private int $customerId;

    private int $productId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(ReturnsAnalysisService::class);
        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
        $this->customerId = DB::table('customers')->insertGetId(['code' => 'CUS-RA', 'name' => 'عميل']);
        $this->productId = DB::table('products')->insertGetId(['code' => 'PRD-RA', 'name' => 'صنف', 'unit_of_measure' => 'piece']);
    }

    public function test_returns_rate_summary_and_by_product(): void
    {
        $sale = $this->invoice('sale', 1000, '2026-05-10');
        $this->item($sale, 1000);

        $return = $this->invoice('sale_return', 100, '2026-05-15');
        $this->item($return, 100);

        $s = $this->svc->summary('2026-05-01', '2026-05-31', null);
        $this->assertEqualsWithDelta(1000.0, $s->sales, 0.001);
        $this->assertEqualsWithDelta(100.0, $s->returns, 0.001);
        $this->assertSame(1, $s->count);
        $this->assertEqualsWithDelta(10.0, $s->rate, 0.1);   // 100 / 1000

        $byProduct = $this->svc->byProduct('2026-05-01', '2026-05-31', null);
        $this->assertCount(1, $byProduct);
        $row = $byProduct->first();
        $this->assertEqualsWithDelta(100.0, $row->returns, 0.001);
        $this->assertEqualsWithDelta(1000.0, $row->sales, 0.001);
        $this->assertEqualsWithDelta(10.0, $row->rate, 0.1);
    }

    private function invoice(string $type, float $total, string $date): int
    {
        return DB::table('invoices')->insertGetId([
            'reference_number' => 'INV-RA'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->whId,
            'customer_id' => $this->customerId,
            'type' => $type,
            'status' => 'confirmed',
            'payment_type' => 'cash',
            'total_amount' => $total,
            'paid_amount' => 0,
            'invoice_date' => $date,
        ]);
    }

    private function item(int $invoiceId, float $total): void
    {
        DB::table('invoice_items')->insert([
            'invoice_id' => $invoiceId,
            'product_id' => $this->productId,
            'quantity' => 1,
            'unit_price' => $total,
            'total' => $total,
        ]);
    }
}
