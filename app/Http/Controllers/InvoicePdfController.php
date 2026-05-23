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
            'customer'     => fn ($q) => $q->withTrashed(),
            'businessUnit',
            'warehouse',
            'createdBy',
        ])->findOrFail($id);

        $company = CompanySetting::first();

        return PdfService::stream(
            'pdf.invoice',
            compact('invoice', 'company'),
            'invoice-' . $invoice->reference_number . '.pdf'
        );
    }
}
