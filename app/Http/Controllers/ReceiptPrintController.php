<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceiptPrintController extends Controller
{
    public function show(Receipt $receipt): Response|string
    {
        $user = auth()->user();

        // تحقق من الصلاحية والوحدة
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

        return view('print.receipt', compact('receipt'));
    }
}
