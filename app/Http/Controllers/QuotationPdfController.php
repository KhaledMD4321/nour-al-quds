<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationPdfController extends Controller
{
    public function show(int $id)
    {
        $quotation = Invoice::with([
            'items.product',
            'customer',
            'businessUnit',
            'warehouse',
            'createdBy',
        ])->where('type', 'quotation')->findOrFail($id);

        $company = CompanySetting::first();

        $pdf = Pdf::loadView('pdf.quotation', compact('quotation', 'company'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('quotation-' . $quotation->reference_number . '.pdf');
    }
}
