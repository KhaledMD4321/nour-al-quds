<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

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

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'company'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('quotation-' . $invoice->reference_number . '.pdf');
    }
}
