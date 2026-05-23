<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\PdfService;
use Illuminate\Http\Request;

class CustomerStatementPrintController extends Controller
{
    public function show(Request $request): string|\Illuminate\Contracts\View\View
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin() && ! $user?->can('accounting.ledger.view')) {
            abort(403);
        }

        $customerId = $request->integer('customer');
        $from       = $request->string('from')->toString() ?: null;
        $to         = $request->string('to')->toString()   ?: null;

        $customer = Customer::findOrFail($customerId);

        // ── رصيد أول المدة ──────────────────────────────────────────────────
        $opening = (float) ($customer->opening_balance ?? 0);

        if ($from) {
            $invBefore = (float) Invoice::where('customer_id', $customerId)
                ->where('type', 'sale')
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereDate('invoice_date', '<', $from)
                ->sum('total_amount');

            $retBefore = (float) Invoice::where('customer_id', $customerId)
                ->where('type', 'sale_return')
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereDate('invoice_date', '<', $from)
                ->sum('total_amount');

            $recBefore = (float) Receipt::where('customer_id', $customerId)
                ->whereDate('receipt_date', '<', $from)
                ->sum('amount');

            $opening = $opening + $invBefore - $retBefore - $recBefore;
        }

        // ── سطور الفترة ─────────────────────────────────────────────────────
        $lines = collect();

        Invoice::where('customer_id', $customerId)
            ->where('type', 'sale')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->when($from, fn ($q) => $q->whereDate('invoice_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('invoice_date', '<=', $to))
            ->orderBy('invoice_date')
            ->each(fn ($inv) => $lines->push((object) [
                'date'        => $inv->invoice_date,
                'reference'   => $inv->reference_number,
                'description' => 'فاتورة مبيعات',
                'debit'       => (float) $inv->total_amount,
                'credit'      => 0.0,
            ]));

        Invoice::where('customer_id', $customerId)
            ->where('type', 'sale_return')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->when($from, fn ($q) => $q->whereDate('invoice_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('invoice_date', '<=', $to))
            ->orderBy('invoice_date')
            ->each(fn ($ret) => $lines->push((object) [
                'date'        => $ret->invoice_date,
                'reference'   => $ret->reference_number,
                'description' => 'مرتجع مبيعات',
                'debit'       => 0.0,
                'credit'      => (float) $ret->total_amount,
            ]));

        Receipt::where('customer_id', $customerId)
            ->when($from, fn ($q) => $q->whereDate('receipt_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('receipt_date', '<=', $to))
            ->orderBy('receipt_date')
            ->each(function ($rec) use ($lines) {
                $method = match ($rec->payment_method) {
                    'cash'          => 'كاش',
                    'cheque'        => 'شيك',
                    'bank_transfer' => 'تحويل بنكي',
                    default         => $rec->payment_method,
                };
                $lines->push((object) [
                    'date'        => $rec->receipt_date,
                    'reference'   => $rec->receipt_number,
                    'description' => "سند قبض ({$method})",
                    'debit'       => 0.0,
                    'credit'      => (float) $rec->amount,
                ]);
            });

        $lines       = $lines->sortBy('date')->values();
        $totalDebit  = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');
        $closing     = $opening + $totalDebit - $totalCredit;

        return PdfService::stream(
            'print.customer-statement',
            compact('customer', 'lines', 'opening', 'totalDebit', 'totalCredit', 'closing', 'from', 'to'),
            "statement-{$customer->code}.pdf"
        );
    }
}
