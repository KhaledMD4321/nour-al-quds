<?php

namespace Tests\Feature;

use App\Modules\Reports\CollectionsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * يثبّت مؤشرات التحصيل: الذمم، DSO، معدل التحصيل، والاتجاه الشهري.
 */
class CollectionsServiceTest extends TestCase
{
    use RefreshDatabase;

    private CollectionsService $svc;

    private int $buId;

    private int $whId;

    private int $userId;

    private int $customerId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = app(CollectionsService::class);

        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
        $this->userId = DB::table('users')->insertGetId(['name' => 'T', 'email' => 't@test.local', 'password' => 'x']);
        $this->customerId = DB::table('customers')->insertGetId(['code' => 'CUS-COL', 'name' => 'عميل']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_summary_computes_ar_dso_collection_rate_and_overdue(): void
    {
        Carbon::setTestNow('2026-05-24');

        // ذمة مفتوحة: 1000 محصّل منها 200 → 800 (تاريخ استحقاق مستقبلي → جاري، غير متأخر)
        $this->invoice('sale', 'confirmed', 1000, '2026-05-10', paid: 200, due: '2026-06-30');
        // فاتورة مدفوعة بالكامل → ضمن المبيعات، ليست ذمة
        $this->invoice('sale', 'paid', 500, '2026-05-15', paid: 500);
        // مرتجع → يقلّل صافي المبيعات
        $this->invoice('sale_return', 'confirmed', 100, '2026-05-20');
        // تحصيل
        $this->receipt(300, '2026-05-12');

        $s = $this->svc->summary('2026-05-01', '2026-05-31', null);

        $this->assertEqualsWithDelta(800.0, $s->ar, 0.001);
        $this->assertEqualsWithDelta(1400.0, $s->sales, 0.001);   // 1000 + 500 − 100
        $this->assertEqualsWithDelta(300.0, $s->collections, 0.001);
        $this->assertSame(31, $s->days);
        $this->assertSame(18, $s->dso);                           // round(800 × 31 / 1400)
        $this->assertEqualsWithDelta(21.4, $s->collection_rate, 0.1);
        $this->assertEqualsWithDelta(0.0, $s->overdue, 0.001);    // الاستحقاق مستقبلي
    }

    public function test_monthly_trend_returns_sales_and_collections(): void
    {
        Carbon::setTestNow('2026-05-24');

        $this->invoice('sale', 'confirmed', 1400, '2026-05-10');
        $this->receipt(300, '2026-05-12');

        $trend = $this->svc->monthlyTrend(6, null);

        $this->assertCount(6, $trend);
        $may = $trend->last();
        $this->assertEqualsWithDelta(1400.0, $may->sales, 0.001);
        $this->assertEqualsWithDelta(300.0, $may->collections, 0.001);
        $this->assertEqualsWithDelta(1100.0, $may->gap, 0.001);
    }

    private function invoice(string $type, string $status, float $total, string $date, float $paid = 0, ?string $due = null): void
    {
        DB::table('invoices')->insert([
            'reference_number' => 'INV-COL'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->whId,
            'customer_id' => $this->customerId,
            'type' => $type,
            'status' => $status,
            'payment_type' => 'credit',
            'total_amount' => $total,
            'paid_amount' => $paid,
            'invoice_date' => $date,
            'due_date' => $due,
        ]);
    }

    private function receipt(float $amount, string $date): void
    {
        DB::table('receipts')->insert([
            'receipt_number' => 'RCP-COL'.(++$this->n),
            'customer_id' => $this->customerId,
            'business_unit_id' => $this->buId,
            'amount' => $amount,
            'payment_method' => 'cash',
            'receipt_date' => $date,
            'created_by' => $this->userId,
        ]);
    }
}
