<?php

namespace Tests\Feature;

use App\Modules\Reports\CustomerInsightsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerInsightsServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerInsightsService $svc;

    private int $buId;

    private int $whId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(CustomerInsightsService::class);
        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_top_customers_have_share_and_cumulative_percentages(): void
    {
        $a = $this->customer('عميل أ');
        $b = $this->customer('عميل ب');
        $this->invoice($a, 800, '2026-05-10');
        $this->invoice($b, 200, '2026-05-11');

        $rows = $this->svc->topCustomers('2026-05-01', '2026-05-31', null);

        $this->assertCount(2, $rows);
        $first = $rows->first();
        $this->assertEqualsWithDelta(800.0, $first->total, 0.001);
        $this->assertEqualsWithDelta(80.0, $first->share_pct, 0.1);
        $this->assertEqualsWithDelta(80.0, $first->cumulative_pct, 0.1);

        $second = $rows->last();
        $this->assertEqualsWithDelta(20.0, $second->share_pct, 0.1);
        $this->assertEqualsWithDelta(100.0, $second->cumulative_pct, 0.1);
    }

    public function test_inactive_customers_lists_only_lapsed_buyers(): void
    {
        Carbon::setTestNow('2026-05-24');

        $lapsed = $this->customer('متوقف');
        $active = $this->customer('نشط');
        $this->invoice($lapsed, 500, '2026-01-01');  // قديم → متوقف
        $this->invoice($active, 300, '2026-05-20');   // حديث → نشط

        $rows = $this->svc->inactiveCustomers(60, null);

        $this->assertCount(1, $rows);
        $this->assertSame('متوقف', $rows->first()->customer_name);
        $this->assertEqualsWithDelta(500.0, $rows->first()->lifetime, 0.001);
        $this->assertSame(1, $rows->first()->orders);
    }

    private function customer(string $name): int
    {
        return DB::table('customers')->insertGetId([
            'code' => 'CUS-CI'.(++$this->n),
            'name' => $name,
        ]);
    }

    private function invoice(int $customerId, float $total, string $date): void
    {
        DB::table('invoices')->insert([
            'reference_number' => 'INV-CI'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->whId,
            'customer_id' => $customerId,
            'type' => 'sale',
            'status' => 'confirmed',
            'payment_type' => 'cash',
            'total_amount' => $total,
            'paid_amount' => 0,
            'invoice_date' => $date,
        ]);
    }
}
