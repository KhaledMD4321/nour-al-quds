<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PdfService;

class PaymentPrintController extends Controller
{
    public function show(Payment $payment)
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin() && ! $user?->can('finance.payment.print')) {
            abort(403);
        }

        if (! $user->isSuperAdmin() && $payment->business_unit_id !== $user->business_unit_id) {
            abort(403);
        }

        $payment->load([
            'supplier',
            'purchaseInvoice',
            'treasury',
            'businessUnit',
            'expenseAccount',
            'createdBy',
            'journalEntry',
        ]);

        return PdfService::stream(
            'print.payment',
            compact('payment'),
            "payment-{$payment->payment_number}.pdf"
        );
    }
}
