<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Modules\Accounting\LedgerService;
use App\Services\PdfService;
use Illuminate\Http\Request;

class CustomerStatementPrintController extends Controller
{
    public function show(Request $request)
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin() && ! $user?->can('accounting.ledger.view')) {
            abort(403);
        }

        $request->validate([
            'customer' => ['required', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'pdf' => ['nullable', 'boolean'],
        ]);

        $customerId = $request->integer('customer');
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        $customer = Customer::findOrFail($customerId);

        $statement = app(LedgerService::class)->customerStatement($customerId, $from, $to);

        $data = [
            'customer' => $customer,
            'lines' => $statement['lines'],
            'opening' => $statement['opening'],
            'totalDebit' => $statement['totalDebit'],
            'totalCredit' => $statement['totalCredit'],
            'closing' => $statement['closing'],
            'from' => $from,
            'to' => $to,
        ];

        // ?pdf=1 → تنزيل PDF مباشرة
        if (request()->boolean('pdf')) {
            return PdfService::stream(
                'pdf.customer-statement',
                $data,
                "statement-{$customer->code}.pdf"
            );
        }

        // الافتراضي: معاينة HTML
        return view('print.customer-statement', $data);
    }
}
