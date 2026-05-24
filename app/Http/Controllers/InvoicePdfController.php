<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\PdfService;

class InvoicePdfController extends Controller
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
        ])->findOrFail($id);

        $company = CompanySetting::first();

        // ?pdf=1 → تنزيل PDF مباشرة
        if (request()->boolean('pdf')) {
            return PdfService::stream(
                'pdf.invoice',
                compact('invoice', 'company'),
                'invoice-'.$invoice->reference_number.'.pdf'
            );
        }

        // الافتراضي: معاينة HTML في المتصفح مع شريط الطباعة
        return view('print.invoice', compact('invoice', 'company'));
    }
}
