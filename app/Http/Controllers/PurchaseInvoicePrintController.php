<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PurchaseInvoice;
use Barryvdh\DomPDF\Facade\Pdf;

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

        $pdf = Pdf::loadView('pdf.purchase-invoice', [
            'invoice' => $purchaseInvoice,
            'company' => $company,
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("فاتورة-شراء-{$purchaseInvoice->reference_number}.pdf");
    }
}
