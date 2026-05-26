<?php

namespace Tests\Feature;

use App\Modules\Reports\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * يثبّت تقرير دوران المخزون والركود:
 *   - الدوران = (تكلفة المبيعات سنوياً) ÷ قيمة المخزون + أيام التغطية
 *   - الراكد = أصناف لها رصيد بلا حركة منذ المدة
 */
class InventoryTurnoverReportTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $svc;

    private int $buId;

    private int $whId;

    private int $companyId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = app(ReportService::class);

        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
        $this->companyId = DB::table('companies')->insertGetId(['name' => 'نصار للمواسير']);
    }

    public function test_inventory_turnover_per_product(): void
    {
        $p = $this->product();
        $this->stock($p, 50, 60);                                   // قيمة المخزون = 3000
        $this->movement($p, 30, 'out', '2026-06-15 10:00:00', invoice: true); // تكلفة = 30×60 = 1800

        // الفترة سنة كاملة (365 يوم) → الدوران = 1800/3000 = 0.6
        $rows = $this->svc->inventoryTurnover('2026-01-01', '2026-12-31', null);

        $this->assertCount(1, $rows);
        $r = $rows->first();
        $this->assertEqualsWithDelta(3000.0, $r->stock_value, 0.001);
        $this->assertEqualsWithDelta(1800.0, $r->cogs, 0.001);
        $this->assertEqualsWithDelta(30.0, $r->qty_sold, 0.001);
        $this->assertEqualsWithDelta(0.6, $r->turnover, 0.01);
        $this->assertSame(608, $r->days_of_inventory);  // 3000 ÷ (1800/365)
    }

    public function test_dead_stock_lists_only_unmoved_items(): void
    {
        $dead = $this->product();
        $this->stock($dead, 10, 25);  // قيمة مجمّدة 250، بلا حركة

        $active = $this->product();
        $this->stock($active, 5, 100);
        $this->movement($active, 2, 'out', now()->toDateTimeString()); // تحرّك حديثاً → ليس راكداً

        $rows = $this->svc->deadStock(90, null);

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(250.0, $rows->first()->total_value, 0.001);
        $this->assertNull($rows->first()->last_movement);
    }

    // ── أدوات ──────────────────────────────────────────────────────────

    private function product(): int
    {
        return DB::table('products')->insertGetId([
            'code' => 'PRD-IT'.(++$this->n),
            'name' => 'صنف اختبار',
            'unit_of_measure' => 'piece',
            'company_id' => $this->companyId,
        ]);
    }

    private function stock(int $productId, float $qty, float $avgCost): void
    {
        DB::table('stock')->insert([
            'warehouse_id' => $this->whId,
            'product_id' => $productId,
            'quantity' => $qty,
            'avg_cost' => $avgCost,
        ]);
    }

    private function movement(int $productId, float $qty, string $type, string $at, bool $invoice = false): void
    {
        DB::table('stock_movements')->insert([
            'warehouse_id' => $this->whId,
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $qty,
            'balance_after' => 0,
            'reference_type' => $invoice ? 'invoice' : null,
            'reference_id' => $invoice ? 1 : null,
            'created_at' => $at,
        ]);
    }
}
