<?php

namespace Tests\Feature;

use App\Modules\Reports\ExecutiveDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * يثبّت منطق مؤشرات اللوحة التنفيذية الجديد:
 *   - نمو المبيعات شهرياً/سنوياً (MoM / YoY) بحساب تواريخ صحيح
 *   - عدّ العملاء المتجاوزين لحد الائتمان
 */
class ExecutiveDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExecutiveDashboardService $svc;

    private int $buId;

    private int $warehouseId;

    private int $userId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = app(ExecutiveDashboardService::class);

        $this->buId = DB::table('business_units')->insertGetId([
            'name' => 'وحدة اختبار',
            'type' => 'distribution',
        ]);
        $this->warehouseId = DB::table('warehouses')->insertGetId([
            'name' => 'مخزن اختبار',
            'business_unit_id' => $this->buId,
        ]);
        $this->userId = DB::table('users')->insertGetId([
            'name' => 'Tester',
            'email' => 'tester@test.local',
            'password' => 'x',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // مسح الساعة الثابتة
        parent::tearDown();
    }

    public function test_sales_growth_computes_month_and_year_deltas(): void
    {
        Carbon::setTestNow('2026-05-24');

        $c = $this->customer();
        $this->invoice($c, 1000, '2026-05-10'); // هذا الشهر + هذه السنة
        $this->invoice($c, 400, '2026-04-15');  // الشهر الماضي (نفس السنة)
        $this->invoice($c, 500, '2025-05-10');  // العام الماضي

        $g = $this->svc->salesGrowth(null);

        // شهري: 1000 الحالي مقابل 400 السابق
        $this->assertEqualsWithDelta(1000.0, $g['month']['current'], 0.001);
        $this->assertEqualsWithDelta(400.0, $g['month']['previous'], 0.001);
        $this->assertEqualsWithDelta(150.0, $g['month']['delta'], 0.001);
        $this->assertTrue($g['month']['up']);

        // سنوي: (1000+400) مقابل 500
        $this->assertEqualsWithDelta(1400.0, $g['year']['current'], 0.001);
        $this->assertEqualsWithDelta(500.0, $g['year']['previous'], 0.001);
        $this->assertEqualsWithDelta(180.0, $g['year']['delta'], 0.001);
    }

    public function test_sales_growth_handles_no_previous_data(): void
    {
        Carbon::setTestNow('2026-05-24');

        $c = $this->customer();
        $this->invoice($c, 750, '2026-05-12'); // مبيعات حالية فقط، لا سابقة

        $g = $this->svc->salesGrowth(null);

        $this->assertEqualsWithDelta(100.0, $g['month']['delta'], 0.001); // نمو من صفر
        $this->assertTrue($g['month']['up']);
    }

    public function test_customers_over_credit_limit_counts_only_real_breaches(): void
    {
        $over = $this->customer(creditLimit: 1000);
        $this->invoice($over, 1500, '2026-05-10', paymentType: 'credit', status: 'confirmed'); // 1500 > 1000 ✓

        $under = $this->customer(creditLimit: 5000);
        $this->invoice($under, 1000, '2026-05-10', paymentType: 'credit', status: 'confirmed'); // تحت الحد

        $noLimit = $this->customer(creditLimit: 0);
        $this->invoice($noLimit, 9999, '2026-05-10', paymentType: 'credit', status: 'confirmed'); // بدون حد → مستبعد

        $cash = $this->customer(creditLimit: 500);
        $this->invoice($cash, 2000, '2026-05-10', paymentType: 'cash', status: 'confirmed'); // نقدي → مستبعد

        $this->assertSame(1, $this->svc->customersOverCreditLimit(null));
    }

    // ── أدوات إنشاء بيانات الاختبار ──────────────────────────────────────

    private function customer(float $creditLimit = 0): int
    {
        return DB::table('customers')->insertGetId([
            'code' => 'CUS-T'.(++$this->n),
            'name' => 'عميل اختبار',
            'credit_limit' => $creditLimit,
        ]);
    }

    private function invoice(int $customerId, float $total, string $date, string $paymentType = 'cash', string $status = 'confirmed', float $paid = 0): int
    {
        return DB::table('invoices')->insertGetId([
            'reference_number' => 'INV-T'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->warehouseId,
            'customer_id' => $customerId,
            'type' => 'sale',
            'status' => $status,
            'payment_type' => $paymentType,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'invoice_date' => $date,
        ]);
    }
}
