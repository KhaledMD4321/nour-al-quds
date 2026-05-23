<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\QuickSale;
use App\Services\PdfService;

class QuickSaleReceiptController extends Controller
{
    public function show(int $id)
    {
        $sale = QuickSale::with([
            'items.product',
            'businessUnit',
            'warehouse',
            'createdBy',
        ])->findOrFail($id);

        $company = CompanySetting::first();

        // A5 للإيصال السريع
        return PdfService::streamA5(
            'pdf.quick-sale-receipt',
            compact('sale', 'company'),
            'receipt-' . $sale->reference_number . '.pdf'
        );
    }
}
