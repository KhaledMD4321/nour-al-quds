<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\PdfService;

class QuotationPdfController extends Controller
{
    public function show(int $id)
    {
        $invoice = Invoice::with([
            'items.product.company',
            'customer',
            'businessUnit',
            'warehouse',
            'createdBy',
        ])->where('type', 'quotation')->findOrFail($id);

        $company = CompanySetting::first();

        return PdfService::stream(
            'pdf.invoice',
            compact('invoice', 'company'),
            'quotation-' . $invoice->reference_number . '.pdf'
        );
    }
}
