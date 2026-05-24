<?php

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_each_case_has_an_arabic_label(): void
    {
        $this->assertSame('كاش', PaymentMethod::Cash->label());
        $this->assertSame('شيك', PaymentMethod::Cheque->label());
        $this->assertSame('تحويل بنكي', PaymentMethod::BankTransfer->label());
    }

    public function test_label_for_resolves_known_values(): void
    {
        $this->assertSame('كاش', PaymentMethod::labelFor('cash'));
        $this->assertSame('شيك', PaymentMethod::labelFor('cheque'));
        $this->assertSame('تحويل بنكي', PaymentMethod::labelFor('bank_transfer'));
    }

    public function test_label_for_returns_raw_value_when_unknown(): void
    {
        $this->assertSame('visa', PaymentMethod::labelFor('visa'));
    }

    public function test_label_for_returns_dash_when_empty(): void
    {
        $this->assertSame('—', PaymentMethod::labelFor(null));
        $this->assertSame('—', PaymentMethod::labelFor(''));
    }

    public function test_options_map_values_to_arabic_labels(): void
    {
        $this->assertSame([
            'cash' => 'كاش',
            'cheque' => 'شيك',
            'bank_transfer' => 'تحويل بنكي',
        ], PaymentMethod::options());
    }
}
