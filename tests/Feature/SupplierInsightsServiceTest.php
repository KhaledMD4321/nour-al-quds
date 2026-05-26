<?php

namespace Tests\Feature;

use App\Modules\Reports\SupplierInsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupplierInsightsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SupplierInsightsService $svc;

    private int $buId;

    private int $whId;

    private int $userId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(SupplierInsightsService::class);
        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
        $this->userId = DB::table('users')->insertGetId(['name' => 'T', 'email' => 't@test.local', 'password' => 'x']);
    }

    public function test_dpo_and_scorecard(): void
    {
        $s1 = $this->supplier('مورد أ');
        $s2 = $this->supplier('مورد ب');

        $pi1 = $this->purchase($s1, 'confirmed', 1000, 400, '2026-05-05'); // مستحق 600
        $this->purchase($s2, 'paid', 500, 500, '2026-05-06');             // مسدّد بالكامل
        $this->addReturn($s1, $pi1, 'confirmed', 100, '2026-05-10');

        $dpo = $this->svc->dpo('2026-05-01', '2026-05-31', null);
        $this->assertEqualsWithDelta(600.0, $dpo->ap, 0.001);
        $this->assertEqualsWithDelta(1400.0, $dpo->purchases, 0.001);  // 1500 − 100
        $this->assertSame(31, $dpo->days);
        $this->assertSame(13, $dpo->dpo);                              // round(600 × 31 / 1400)

        $rows = $this->svc->scorecard('2026-05-01', '2026-05-31', null);
        $this->assertCount(2, $rows);

        $a = $rows->firstWhere('supplier', 'مورد أ');
        $this->assertEqualsWithDelta(1000.0, $a->purchases, 0.001);
        $this->assertEqualsWithDelta(600.0, $a->outstanding, 0.001);
        $this->assertEqualsWithDelta(100.0, $a->returns, 0.001);
        $this->assertEqualsWithDelta(10.0, $a->return_rate, 0.1);

        $b = $rows->firstWhere('supplier', 'مورد ب');
        $this->assertEqualsWithDelta(0.0, $b->returns, 0.001);
    }

    private function supplier(string $name): int
    {
        return DB::table('suppliers')->insertGetId(['code' => 'SUP-SI'.(++$this->n), 'name' => $name]);
    }

    private function purchase(int $supplierId, string $status, float $total, float $paid, string $date): int
    {
        return DB::table('purchase_invoices')->insertGetId([
            'reference_number' => 'PINV-SI'.(++$this->n),
            'supplier_id' => $supplierId,
            'warehouse_id' => $this->whId,
            'business_unit_id' => $this->buId,
            'invoice_date' => $date,
            'status' => $status,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'created_by' => $this->userId,
        ]);
    }

    private function addReturn(int $supplierId, int $purchaseInvoiceId, string $status, float $total, string $date): void
    {
        DB::table('purchase_returns')->insert([
            'reference_number' => 'PRR-SI'.(++$this->n),
            'purchase_invoice_id' => $purchaseInvoiceId,
            'supplier_id' => $supplierId,
            'warehouse_id' => $this->whId,
            'business_unit_id' => $this->buId,
            'return_date' => $date,
            'status' => $status,
            'total_amount' => $total,
            'created_by' => $this->userId,
        ]);
    }
}
