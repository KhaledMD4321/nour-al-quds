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
            'items' => fn ($q) => $q->with([
                'product' => fn ($q) => $q->withTrashed()->with(['company']),
            ]),
            'customer' => fn ($q) => $q->withTrashed(),
            'businessUnit',
            'warehouse',
            'createdBy',
        ])->where('type', 'quotation')->findOrFail($id);

        $company = CompanySetting::first();

        // ?pdf=1 → تنزيل PDF مباشرة
        if (request()->boolean('pdf')) {
            return PdfService::stream(
                'pdf.invoice',
                compact('invoice', 'company'),
                'quotation-'.$invoice->reference_number.'.pdf'
            );
        }

        // الافتراضي: معاينة HTML
        return view('print.invoice', compact('invoice', 'company'));
    }
}
