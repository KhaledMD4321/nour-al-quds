<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PurchaseInvoice;
use App\Services\PdfService;

class PurchaseInvoicePrintController extends Controller
{
    public function print(PurchaseInvoice $purchaseInvoice)
    {
        abort_unless(auth()->check(), 403);

        $purchaseInvoice->load([
            'supplier' => fn ($q) => $q->withTrashed(),
            'items' => fn ($q) => $q->with([
                'product' => fn ($q) => $q->withTrashed(),
            ]),
            'businessUnit',
            'warehouse',
            'createdBy',
        ]);

        $company = CompanySetting::first();
        $invoice = $purchaseInvoice;   // alias for view variable consistency

        // ?pdf=1 → تنزيل PDF مباشرة
        if (request()->boolean('pdf')) {
            return PdfService::stream(
                'pdf.purchase-invoice',
                ['invoice' => $invoice, 'company' => $company],
                "purchase-invoice-{$purchaseInvoice->reference_number}.pdf"
            );
        }

        // الافتراضي: معاينة HTML
        return view('print.purchase-invoice', compact('invoice', 'company'));
    }
}
