<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\PdfService;

class ReceiptPrintController extends Controller
{
    public function show(Receipt $receipt)
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin() && ! $user?->can('finance.receipt.print')) {
            abort(403);
        }

        if (! $user->isSuperAdmin() && $receipt->business_unit_id !== $user->business_unit_id) {
            abort(403);
        }

        $receipt->load([
            'customer',
            'invoice',
            'treasury',
            'businessUnit',
            'createdBy',
            'journalEntry',
        ]);

        return PdfService::stream(
            'print.receipt',
            compact('receipt'),
            "receipt-{$receipt->receipt_number}.pdf"
        );
    }
}
