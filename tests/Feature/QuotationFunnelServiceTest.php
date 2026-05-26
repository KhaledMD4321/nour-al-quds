<?php

namespace Tests\Feature;

use App\Modules\Reports\QuotationFunnelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuotationFunnelServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuotationFunnelService $svc;

    private int $buId;

    private int $whId;

    private int $customerId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(QuotationFunnelService::class);
        $this->buId = DB::table('business_units')->insertGetId(['name' => 'وحدة', 'type' => 'distribution']);
        $this->whId = DB::table('warehouses')->insertGetId(['name' => 'مخزن', 'business_unit_id' => $this->buId]);
        $this->customerId = DB::table('customers')->insertGetId(['code' => 'CUS-QF', 'name' => 'عميل']);
    }

    public function test_win_rate_and_open_quotations(): void
    {
        $q1 = $this->invoice('quotation', 'confirmed', 1000, '2026-05-05');
        $q2 = $this->invoice('quotation', 'confirmed', 500, '2026-05-06');
        $this->invoice('quotation', 'cancelled', 300, '2026-05-07');   // مستبعد
        $this->invoice('sale', 'confirmed', 1000, '2026-05-10', quotationId: $q1); // تحويل Q1

        $s = $this->svc->summary('2026-05-01', '2026-05-31', null);

        $this->assertSame(2, $s->total);            // Q1 + Q2 (الملغى مستبعد)
        $this->assertEqualsWithDelta(1500.0, $s->total_value, 0.001);
        $this->assertSame(1, $s->converted);        // Q1 فقط
        $this->assertEqualsWithDelta(1000.0, $s->converted_value, 0.001);
        $this->assertSame(1, $s->pending);
        $this->assertEqualsWithDelta(50.0, $s->win_rate, 0.1);

        $open = $this->svc->openQuotations('2026-05-01', '2026-05-31', null);
        $this->assertCount(1, $open);               // Q2 فقط
        $this->assertEqualsWithDelta(500.0, $open->first()->total, 0.001);
    }

    private function invoice(string $type, string $status, float $total, string $date, ?int $quotationId = null): int
    {
        return DB::table('invoices')->insertGetId([
            'reference_number' => strtoupper($type === 'quotation' ? 'QUO' : 'INV').'-'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->whId,
            'customer_id' => $this->customerId,
            'type' => $type,
            'status' => $status,
            'payment_type' => 'cash',
            'total_amount' => $total,
            'paid_amount' => 0,
            'invoice_date' => $date,
            'quotation_id' => $quotationId,
        ]);
    }
}
