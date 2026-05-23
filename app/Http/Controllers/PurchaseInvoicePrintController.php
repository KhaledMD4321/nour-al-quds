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
            'supplier',
            'items.product',
            'businessUnit',
            'warehouse',
            'createdBy',
        ]);

        $company = CompanySetting::first();

        return PdfService::stream(
            'pdf.purchase-invoice',
            ['invoice' => $purchaseInvoice, 'company' => $company],
            "purchase-invoice-{$purchaseInvoice->reference_number}.pdf"
        );
    }
}
