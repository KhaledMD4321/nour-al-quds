<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;

class SaleReturn extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static string|\UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'مرتجع مبيعات';

    protected static ?string $title = 'إنشاء مرتجع مبيعات';

    // لا يظهر في القائمة الجانبية — يُفتح من زرار المرتجع في الفاتورة
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.sale-return';

    public ?Invoice $invoice = null;

    public function mount(?int $invoice = null): void
    {
        if ($invoice) {
            $this->invoice = Invoice::with(['items.product', 'customer'])->findOrFail($invoice);
        }
    }
}
