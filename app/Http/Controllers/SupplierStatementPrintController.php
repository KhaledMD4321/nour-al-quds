<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Modules\Accounting\LedgerService;
use App\Services\PdfService;
use Illuminate\Http\Request;

class SupplierStatementPrintController extends Controller
{
    public function show(Request $request)
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin() && ! $user?->can('accounting.ledger.view')) {
            abort(403);
        }

        $request->validate([
            'supplier' => ['required', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'pdf' => ['nullable', 'boolean'],
        ]);

        $supplierId = $request->integer('supplier');
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        $supplier = Supplier::findOrFail($supplierId);

        $statement = app(LedgerService::class)->supplierStatement($supplierId, $from, $to);

        $data = [
            'supplier' => $supplier,
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
                'pdf.supplier-statement',
                $data,
                "statement-{$supplier->code}.pdf"
            );
        }

        // الافتراضي: معاينة HTML
        return view('print.supplier-statement', $data);
    }
}
