<?php

namespace Tests\Unit;

use App\Models\Customer;
use Tests\TestCase;

/**
 * يثبّت قاعدة العمل الأهم: الخصم الثلاثي المتتابع (مش تراكمي).
 *   1000 + 10% + 5% + 2%  =  1000 × 0.90 × 0.95 × 0.98  =  837.90
 *   (وليس 1000 × (1 − 0.17) = 830)
 */
class DiscountCalculationTest extends TestCase
{
    public function test_sequential_discount_is_applied_in_order(): void
    {
        $customer = new Customer([
            'default_discount_1' => 10,
            'default_discount_2' => 5,
            'default_discount_3' => 2,
        ]);

        $this->assertEqualsWithDelta(837.90, $customer->calculatePrice(1000), 0.0001);
    }

    public function test_sequential_discount_is_not_cumulative(): void
    {
        $customer = new Customer([
            'default_discount_1' => 10,
            'default_discount_2' => 5,
            'default_discount_3' => 2,
        ]);

        // الخطأ الشائع (تراكمي) = 830 — يجب ألا نحصل عليه
        $this->assertNotEqualsWithDelta(830.00, $customer->calculatePrice(1000), 0.0001);
    }

    public function test_zero_discounts_return_list_price(): void
    {
        $customer = new Customer([
            'default_discount_1' => 0,
            'default_discount_2' => 0,
            'default_discount_3' => 0,
        ]);

        $this->assertEqualsWithDelta(1000.00, $customer->calculatePrice(1000), 0.0001);
    }

    public function test_effective_discount_percent_is_computed_from_sequence(): void
    {
        $customer = new Customer([
            'default_discount_1' => 10,
            'default_discount_2' => 5,
            'default_discount_3' => 2,
        ]);

        // (1 − 0.90×0.95×0.98) × 100 = 16.21%
        $this->assertEqualsWithDelta(16.21, $customer->effective_discount_percent, 0.01);
    }
}
