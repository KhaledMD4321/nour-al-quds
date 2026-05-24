<?php

namespace Tests\Unit;

use App\Models\Invoice;
use PHPUnit\Framework\TestCase;

class InvoiceStatusTest extends TestCase
{
    public function test_status_labels_are_arabic(): void
    {
        $this->assertSame('مسودة', Invoice::statusLabel('draft'));
        $this->assertSame('مؤكدة', Invoice::statusLabel('confirmed'));
        $this->assertSame('مدفوعة', Invoice::statusLabel('paid'));
        $this->assertSame('ملغاة', Invoice::statusLabel('cancelled'));
    }

    public function test_status_label_falls_back_to_raw_value(): void
    {
        $this->assertSame('whatever', Invoice::statusLabel('whatever'));
    }

    public function test_status_colors_map_to_known_palette(): void
    {
        $this->assertSame('success', Invoice::statusColor('paid'));
        $this->assertSame('danger', Invoice::statusColor('cancelled'));
        $this->assertSame('warning', Invoice::statusColor('partially_paid'));
        $this->assertSame('gray', Invoice::statusColor('unknown'));
    }
}
