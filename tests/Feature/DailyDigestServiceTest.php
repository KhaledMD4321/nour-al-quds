<?php

namespace Tests\Feature;

use App\Modules\Notifications\DailyDigestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DailyDigestServiceTest extends TestCase
{
    use RefreshDatabase;

    private DailyDigestService $svc;

    private int $buId;

    private int $warehouseId;

    private int $customerId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = app(DailyDigestService::class);

        $this->buId = DB::table('business_units')->insertGetId([
            'name' => 'وحدة اختبار',
            'type' => 'distribution',
        ]);
        $this->warehouseId = DB::table('warehouses')->insertGetId([
            'name' => 'مخزن اختبار',
            'business_unit_id' => $this->buId,
        ]);
        $this->customerId = DB::table('customers')->insertGetId([
            'code' => 'CUS-D1',
            'name' => 'عميل',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_build_summarizes_yesterday_sales_and_open_invoices(): void
    {
        Carbon::setTestNow('2026-05-24'); // الأمس = 2026-05-23

        // فاتورة أمس مدفوعة بالكامل → تدخل مبيعات الأمس، ليست مفتوحة
        $this->invoice(800, 'paid', '2026-05-23', 800);
        // فاتورة اليوم مؤكدة جزئية الدفع → مفتوحة (محتاجة تحصيل)
        $this->invoice(500, 'confirmed', '2026-05-24', 100);

        $d = $this->svc->build();

        $this->assertSame('2026-05-23', $d['date']);
        $this->assertEqualsWithDelta(800.0, $d['yesterday_sales'], 0.001);
        $this->assertSame(1, $d['yesterday_count']);
        $this->assertSame(1, $d['open_count']);
        $this->assertEqualsWithDelta(400.0, $d['open_total'], 0.001);
    }

    private function invoice(float $total, string $status, string $date, float $paid): void
    {
        DB::table('invoices')->insert([
            'reference_number' => 'INV-D'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->warehouseId,
            'customer_id' => $this->customerId,
            'type' => 'sale',
            'status' => $status,
            'payment_type' => 'cash',
            'total_amount' => $total,
            'paid_amount' => $paid,
            'invoice_date' => $date,
        ]);
    }
}
