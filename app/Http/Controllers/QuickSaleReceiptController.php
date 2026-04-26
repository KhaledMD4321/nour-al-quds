<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\QuickSale;
use Barryvdh\DomPDF\Facade\Pdf;

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

        $pdf = Pdf::loadView('pdf.quick-sale-receipt', compact('sale', 'company'))
            ->setPaper([0, 0, 419.53, 595.28], 'portrait'); // A5

        return $pdf->stream('receipt-' . $sale->reference_number . '.pdf');
    }
}
