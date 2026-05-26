<?php

namespace Tests\Feature;

use App\Modules\Notifications\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private AlertService $svc;

    private int $buId;

    private int $warehouseId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = app(AlertService::class);

        $this->buId = DB::table('business_units')->insertGetId([
            'name' => 'وحدة اختبار',
            'type' => 'distribution',
        ]);
        $this->warehouseId = DB::table('warehouses')->insertGetId([
            'name' => 'مخزن اختبار',
            'business_unit_id' => $this->buId,
        ]);
    }

    public function test_no_alerts_on_a_clean_system(): void
    {
        $this->assertSame([], $this->svc->evaluate());
    }

    public function test_credit_limit_breach_raises_an_alert(): void
    {
        $customer = DB::table('customers')->insertGetId([
            'code' => 'CUS-A1',
            'name' => 'عميل',
            'credit_limit' => 1000,
        ]);

        DB::table('invoices')->insert([
            'reference_number' => 'INV-A1',
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->warehouseId,
            'customer_id' => $customer,
            'type' => 'sale',
            'status' => 'confirmed',
            'payment_type' => 'credit',
            'total_amount' => 1500,
            'paid_amount' => 0,
            'invoice_date' => '2026-05-10',
        ]);

        $keys = array_column($this->svc->evaluate(), 'key');

        $this->assertContains('credit_limit', $keys);
    }
}
