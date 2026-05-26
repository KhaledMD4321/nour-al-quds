<?php

namespace Tests\Feature;

use App\Modules\Reports\CashFlowForecastService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * يثبّت توقّع التدفق النقدي: تصنيف المستحقات حسب تاريخ الاستحقاق
 * في الفترات (متأخر / أسبوعي) وحساب الرصيد التراكمي.
 */
class CashFlowForecastServiceTest extends TestCase
{
    use RefreshDatabase;

    private CashFlowForecastService $svc;

    private int $buId;

    private int $whId;

    private int $userId;

    private int $customerId;

    private int $supplierId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = app(CashFlowForecastService::class);

        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
        $this->userId = DB::table('users')->insertGetId(['name' => 'T', 'email' => 't@test.local', 'password' => 'x']);
        $this->customerId = DB::table('customers')->insertGetId(['code' => 'CUS-CF', 'name' => 'عميل']);
        $this->supplierId = DB::table('suppliers')->insertGetId(['code' => 'SUP-CF', 'name' => 'مورد']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_forecast_buckets_inflows_outflows_and_running_balance(): void
    {
        Carbon::setTestNow('2026-05-24'); // اليوم = 24 مايو، الأسبوع الأول 25–31

        // متأخر (مستحق حتى اليوم): ذمة 1000 مدفوع منها 200 → داخل 800
        $this->receivable(1000, 200, '2026-05-20');

        // الأسبوع الأول: ذمة 500 + شيك وارد 400 = داخل 900 ؛ مدفوعات 300 + شيك صادر 100 = خارج 400
        $this->receivable(500, 0, '2026-05-27');
        $this->payable(300, 0, '2026-05-28');
        $this->cheque('incoming', 400, '2026-05-26');
        $this->cheque('outgoing', 100, '2026-05-29');

        $f = $this->svc->forecast('week', 8, null);

        // الرصيد الافتتاحي 0 (لا خزائن)
        $this->assertEqualsWithDelta(0.0, $f['opening_cash'], 0.001);

        // حتى اليوم
        $b0 = $f['buckets'][0];
        $this->assertEqualsWithDelta(800.0, $b0['inflow'], 0.001);
        $this->assertEqualsWithDelta(0.0, $b0['outflow'], 0.001);
        $this->assertEqualsWithDelta(800.0, $b0['running_balance'], 0.001);

        // الأسبوع الأول
        $b1 = $f['buckets'][1];
        $this->assertEqualsWithDelta(900.0, $b1['inflow'], 0.001);
        $this->assertEqualsWithDelta(400.0, $b1['outflow'], 0.001);
        $this->assertEqualsWithDelta(500.0, $b1['net'], 0.001);
        $this->assertEqualsWithDelta(1300.0, $b1['running_balance'], 0.001);

        // الإجماليات
        $this->assertEqualsWithDelta(1700.0, $f['total_inflow'], 0.001);
        $this->assertEqualsWithDelta(400.0, $f['total_outflow'], 0.001);
        $this->assertEqualsWithDelta(1300.0, $f['ending_balance'], 0.001);
    }

    private function receivable(float $total, float $paid, string $due): void
    {
        DB::table('invoices')->insert([
            'reference_number' => 'INV-CF'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->whId,
            'customer_id' => $this->customerId,
            'type' => 'sale',
            'status' => 'confirmed',
            'payment_type' => 'credit',
            'total_amount' => $total,
            'paid_amount' => $paid,
            'invoice_date' => $due,
            'due_date' => $due,
        ]);
    }

    private function payable(float $total, float $paid, string $due): void
    {
        DB::table('purchase_invoices')->insert([
            'reference_number' => 'PINV-CF'.(++$this->n),
            'supplier_id' => $this->supplierId,
            'warehouse_id' => $this->whId,
            'business_unit_id' => $this->buId,
            'invoice_date' => $due,
            'due_date' => $due,
            'status' => 'confirmed',
            'total_amount' => $total,
            'paid_amount' => $paid,
            'created_by' => $this->userId,
        ]);
    }

    private function cheque(string $direction, float $amount, string $due): void
    {
        DB::table('cheques')->insert([
            'cheque_number' => 'CHQ-CF'.(++$this->n),
            'bank_name' => 'بنك',
            'amount' => $amount,
            'issue_date' => '2026-05-01',
            'due_date' => $due,
            'direction' => $direction,
            'status' => 'pending',
            'business_unit_id' => $this->buId,
            'created_by' => $this->userId,
        ]);
    }
}
