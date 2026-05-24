<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Supplier;
use Tests\TestCase;

/**
 * عند نموذج غير محفوظ (لا id) يجب أن يرجع الرصيد = الرصيد الافتتاحي
 * دون لمس قاعدة البيانات عبر LedgerService.
 */
class ModelBalanceGuardTest extends TestCase
{
    public function test_unsaved_customer_returns_opening_balance(): void
    {
        $customer = new Customer(['opening_balance' => 1250.50]);

        $this->assertSame(1250.50, $customer->current_balance);
    }

    public function test_unsaved_supplier_returns_opening_balance(): void
    {
        $supplier = new Supplier(['opening_balance' => 980.25]);

        $this->assertSame(980.25, $supplier->current_balance);
    }
}
