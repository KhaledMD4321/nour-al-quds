<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Response;

class PaymentPrintController extends Controller
{
    public function show(Payment $payment): Response|string
    {
        $user = auth()->user();

        // تحقق من الصلاحية
        if (! $user?->isSuperAdmin() && ! $user?->can('finance.payment.print')) {
            abort(403);
        }

        // تحقق من الوحدة
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

        return view('print.payment', compact('payment'));
    }
}
