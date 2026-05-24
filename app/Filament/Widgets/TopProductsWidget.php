<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends Widget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.top-products';

    public function getProducts(): array
    {
        $user = auth()->user();
        $unitId = ($user && ! $user->isSuperAdmin() && $user->business_unit_id)
            ? $user->business_unit_id : null;

        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.type', 'sale')
            ->whereNotIn('invoices.status', ['draft', 'cancelled'])
            ->whereMonth('invoices.invoice_date', today()->month)
            ->whereYear('invoices.invoice_date', today()->year)
            ->when($unitId, fn ($q) => $q->where('invoices.business_unit_id', $unitId))
            ->selectRaw('
                invoice_items.product_id,
                products.name  AS product_name,
                SUM(invoice_items.quantity) AS total_qty,
                SUM(invoice_items.total)    AS total_value
            ')
            ->groupBy('invoice_items.product_id', 'products.name')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
