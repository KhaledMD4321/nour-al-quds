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
            'items' => fn ($q) => $q->with([
                'product' => fn ($q) => $q->withTrashed(),
            ]),
            'businessUnit',
            'warehouse',
            'createdBy',
        ])->findOrFail($id);

        $company = CompanySetting::first();

        // ?pdf=1 → تنزيل PDF (A5)
        if (request()->boolean('pdf')) {
            return PdfService::streamA5(
                'pdf.quick-sale-receipt',
                compact('sale', 'company'),
                'receipt-'.$sale->reference_number.'.pdf'
            );
        }

        // الافتراضي: معاينة HTML
        return view('print.quick-sale-receipt', compact('sale', 'company'));
    }
}
