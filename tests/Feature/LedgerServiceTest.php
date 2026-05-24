<?php

namespace Tests\Feature;

use App\Modules\Accounting\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * يثبّت صحّة LedgerService — المصدر الوحيد لحساب الأرصدة والكشوف.
 * السيناريوهات مصمّمة لإثبات إصلاح الباگ القديم:
 *   - عروض الأسعار والمسودات لا تدخل رصيد العميل
 *   - المرتجعات تُطرح فعلاً
 *   - رصيد المورد يستثني الفواتير غير المؤكدة والمدفوعات غير التابعة للموردين
 */
class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;

    private int $buId;

    private int $warehouseId;

    private int $userId;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(LedgerService::class);

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

    // ── العملاء ──────────────────────────────────────────────────────────

    public function test_customer_balance_excludes_quotations_and_drafts_and_subtracts_returns(): void
    {
        $c = $this->customer(opening: 100);

        $this->invoice($c, 'sale', 'confirmed', 500, '2026-01-10');        // +500 مدين
        $this->invoice($c, 'sale', 'draft', 999, '2026-01-11');            // مستبعد (مسودة)
        $this->invoice($c, 'quotation', 'confirmed', 777, '2026-01-12');   // مستبعد (عرض سعر)
        $this->invoice($c, 'sale_return', 'confirmed', 50, '2026-01-13');  // -50 دائن
        $this->receipt($c, 200, '2026-01-14');                             // -200 دائن

        // 100 + 500 - 50 - 200 = 350
        $this->assertEqualsWithDelta(350.0, $this->ledger->customerBalance($c), 0.001);
    }

    public function test_customer_statement_closing_equals_balance_and_totals_are_correct(): void
    {
        $c = $this->customer(opening: 100);
        $this->invoice($c, 'sale', 'confirmed', 500, '2026-01-10');
        $this->invoice($c, 'sale_return', 'confirmed', 50, '2026-01-13');
        $this->receipt($c, 200, '2026-01-14');

        $stmt = $this->ledger->customerStatement($c, null, null);

        $this->assertCount(3, $stmt['lines']);
        $this->assertEqualsWithDelta(500.0, $stmt['totalDebit'], 0.001);
        $this->assertEqualsWithDelta(250.0, $stmt['totalCredit'], 0.001);   // 50 + 200
        $this->assertEqualsWithDelta(350.0, $stmt['closing'], 0.001);
        $this->assertEqualsWithDelta($this->ledger->customerBalance($c), $stmt['closing'], 0.001);
    }

    public function test_customer_statement_opening_respects_date_range(): void
    {
        $c = $this->customer(opening: 100);
        $this->invoice($c, 'sale', 'confirmed', 500, '2026-01-10');   // قبل الفترة
        $this->invoice($c, 'sale', 'confirmed', 300, '2026-02-05');   // داخل الفترة

        $stmt = $this->ledger->customerStatement($c, '2026-02-01', '2026-02-28');

        $this->assertEqualsWithDelta(600.0, $stmt['opening'], 0.001);   // 100 + 500
        $this->assertCount(1, $stmt['lines']);
        $this->assertEqualsWithDelta(900.0, $stmt['closing'], 0.001);   // 600 + 300
    }

    public function test_customer_credit_exposure_counts_only_unpaid_credit_invoices(): void
    {
        $c = $this->customer();
        $this->invoice($c, 'sale', 'confirmed', 1000, '2026-01-10', paymentType: 'credit', paid: 300); // 700 متبقّي
        $this->invoice($c, 'sale', 'confirmed', 500, '2026-01-11', paymentType: 'cash');                // مستبعد (نقدي)
        $this->invoice($c, 'sale', 'draft', 800, '2026-01-12', paymentType: 'credit');                  // مستبعد (مسودة)

        $this->assertEqualsWithDelta(700.0, $this->ledger->customerCreditExposure($c), 0.001);
    }

    // ── الموردون ─────────────────────────────────────────────────────────

    public function test_supplier_balance_filters_status_and_payment_category(): void
    {
        $s = $this->supplier(opening: 0);

        $pi = $this->purchaseInvoice($s, 'confirmed', 1000, '2026-01-10');  // +1000 دائن
        $this->purchaseInvoice($s, 'paid', 200, '2026-01-11');              // +200  دائن
        $this->purchaseInvoice($s, 'draft', 5000, '2026-01-12');            // مستبعد (مسودة)
        $this->purchaseReturn($s, $pi, 'confirmed', 100, '2026-01-13');     // -100  مدين
        $this->purchaseReturn($s, $pi, 'draft', 300, '2026-01-14');         // مستبعد (مسودة)
        $this->payment($s, 'supplier_payment', 400, '2026-01-15');          // -400  مدين
        $this->payment($s, 'expense', 999, '2026-01-16');                   // مستبعد (تصنيف آخر)

        // 0 + (1000 + 200) - 100 - 400 = 700
        $this->assertEqualsWithDelta(700.0, $this->ledger->supplierBalance($s), 0.001);
    }

    public function test_supplier_statement_closing_equals_balance(): void
    {
        $s = $this->supplier(opening: 0);
        $pi = $this->purchaseInvoice($s, 'confirmed', 1000, '2026-01-10');
        $this->purchaseReturn($s, $pi, 'confirmed', 100, '2026-01-13');
        $this->payment($s, 'supplier_payment', 400, '2026-01-15');

        $stmt = $this->ledger->supplierStatement($s, null, null);

        $this->assertEqualsWithDelta(1000.0, $stmt['totalCredit'], 0.001);
        $this->assertEqualsWithDelta(500.0, $stmt['totalDebit'], 0.001);    // 100 + 400
        $this->assertEqualsWithDelta(500.0, $stmt['closing'], 0.001);       // 1000 - 500
        $this->assertEqualsWithDelta($this->ledger->supplierBalance($s), $stmt['closing'], 0.001);
    }

    // ── أدوات إنشاء بيانات الاختبار ──────────────────────────────────────

    private function customer(float $opening = 0): int
    {
        return DB::table('customers')->insertGetId([
            'code' => 'CUS-T'.(++$this->n),
            'name' => 'عميل اختبار',
            'opening_balance' => $opening,
        ]);
    }

    private function supplier(float $opening = 0): int
    {
        return DB::table('suppliers')->insertGetId([
            'code' => 'SUP-T'.(++$this->n),
            'name' => 'مورد اختبار',
            'opening_balance' => $opening,
        ]);
    }

    private function invoice(int $customerId, string $type, string $status, float $total, string $date, string $paymentType = 'cash', float $paid = 0): int
    {
        return DB::table('invoices')->insertGetId([
            'reference_number' => 'INV-T'.(++$this->n),
            'business_unit_id' => $this->buId,
            'warehouse_id' => $this->warehouseId,
            'customer_id' => $customerId,
            'type' => $type,
            'status' => $status,
            'payment_type' => $paymentType,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'invoice_date' => $date,
        ]);
    }

    private function receipt(int $customerId, float $amount, string $date): int
    {
        return DB::table('receipts')->insertGetId([
            'receipt_number' => 'RCP-T'.(++$this->n),
            'customer_id' => $customerId,
            'business_unit_id' => $this->buId,
            'amount' => $amount,
            'payment_method' => 'cash',
            'receipt_date' => $date,
            'created_by' => $this->userId,
        ]);
    }

    private function purchaseInvoice(int $supplierId, string $status, float $total, string $date): int
    {
        return DB::table('purchase_invoices')->insertGetId([
            'reference_number' => 'PINV-T'.(++$this->n),
            'supplier_id' => $supplierId,
            'warehouse_id' => $this->warehouseId,
            'business_unit_id' => $this->buId,
            'invoice_date' => $date,
            'status' => $status,
            'total_amount' => $total,
            'created_by' => $this->userId,
        ]);
    }

    private function purchaseReturn(int $supplierId, int $purchaseInvoiceId, string $status, float $total, string $date): int
    {
        return DB::table('purchase_returns')->insertGetId([
            'reference_number' => 'PRR-T'.(++$this->n),
            'purchase_invoice_id' => $purchaseInvoiceId,
            'supplier_id' => $supplierId,
            'warehouse_id' => $this->warehouseId,
            'business_unit_id' => $this->buId,
            'return_date' => $date,
            'status' => $status,
            'total_amount' => $total,
            'created_by' => $this->userId,
        ]);
    }

    private function payment(int $supplierId, string $category, float $amount, string $date): int
    {
        return DB::table('payments')->insertGetId([
            'payment_number' => 'PAY-T'.(++$this->n),
            'supplier_id' => $supplierId,
            'business_unit_id' => $this->buId,
            'amount' => $amount,
            'category' => $category,
            'payment_method' => 'cash',
            'payment_date' => $date,
            'created_by' => $this->userId,
        ]);
    }
}
