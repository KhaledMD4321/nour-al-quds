<?php

namespace Tests\Feature;

use App\Modules\Reports\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $svc;

    private int $buId;

    private int $whId;

    private int $customerId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(BudgetService::class);
        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
        $this->customerId = DB::table('customers')->insertGetId(['code' => 'CUS-BG', 'name' => 'عميل']);
    }

    public function test_monthly_sales_and_comparison(): void
    {
        $this->sale(1000, '2026-03-10');
        $this->sale(500, '2026-03-20');   // مارس = 1500
        $this->sale(800, '2026-07-05');   // يوليو = 800

        $sales = $this->svc->monthlySales(2026, null);
        $this->assertEqualsWithDelta(1500.0, $sales[3], 0.001);
        $this->assertEqualsWithDelta(800.0, $sales[7], 0.001);
        $this->assertEqualsWithDelta(0.0, $sales[1], 0.001);

        $rows = collect($this->svc->comparison(2026, null, [3 => 2000, 7 => 500]));

        $march = $rows->firstWhere('month', 3);
        $this->assertEqualsWithDelta(2000.0, $march->target, 0.001);
        $this->assertEqualsWithDelta(1500.0, $march->actual, 0.001);
        $this->assertEqualsWithDelta(-500.0, $march->variance, 0.001);
        $this->assertEqualsWithDelta(75.0, $march->achievement, 0.1);

        $july = $rows->firstWhere('month', 7);
        $this->assertEqualsWithDelta(160.0, $july->achievement, 0.1);  // 800 / 500
    }

    public function test_monthly_targets_reads_stored_values(): void
    {
        DB::table('sales_targets')->insert([
            'business_unit_id' => $this->buId,
            'year' => 2026,
            'month' => 5,
            'target_amount' => 3000,
        ]);

        $targets = $this->svc->monthlyTargets(2026, $this->buId);

        $this->assertEqualsWithDelta(3000.0, $targets[5], 0.001);
        $this->assertEqualsWithDelta(0.0, $targets[1], 0.001);
    }

    private function sale(float $total, string $date): void
    {
        DB::table('invoices')->insert([
            'reference_number' => 'INV-BG'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->whId,
            'customer_id' => $this->customerId,
            'type' => 'sale',
            'status' => 'confirmed',
            'payment_type' => 'cash',
            'total_amount' => $total,
            'paid_amount' => 0,
            'invoice_date' => $date,
        ]);
    }
}
