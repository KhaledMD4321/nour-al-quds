<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Services\PdfService;
use Illuminate\Http\Request;

class SupplierStatementPrintController extends Controller
{
    public function show(Request $request): string|\Illuminate\Contracts\View\View
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin() && ! $user?->can('accounting.ledger.view')) {
            abort(403);
        }

        $supplierId = $request->integer('supplier');
        $from       = $request->string('from')->toString() ?: null;
        $to         = $request->string('to')->toString()   ?: null;

        $supplier = Supplier::findOrFail($supplierId);

        // ── رصيد أول المدة ──────────────────────────────────────────────────
        $opening = (float) ($supplier->opening_balance ?? 0);

        if ($from) {
            $invBefore = (float) PurchaseInvoice::where('supplier_id', $supplierId)
                ->whereIn('status', ['confirmed', 'paid'])
                ->whereDate('invoice_date', '<', $from)
                ->sum('total_amount');

            $retBefore = (float) PurchaseReturn::where('supplier_id', $supplierId)
                ->whereDate('return_date', '<', $from)
                ->sum('total_amount');

            $payBefore = (float) Payment::where('supplier_id', $supplierId)
                ->whereDate('payment_date', '<', $from)
                ->sum('amount');

            $opening = $opening + $invBefore - $retBefore - $payBefore;
        }

        // ── سطور الفترة ─────────────────────────────────────────────────────
        $lines = collect();

        PurchaseInvoice::where('supplier_id', $supplierId)
            ->whereIn('status', ['confirmed', 'paid'])
            ->when($from, fn ($q) => $q->whereDate('invoice_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('invoice_date', '<=', $to))
            ->orderBy('invoice_date')
            ->each(fn ($inv) => $lines->push((object) [
                'date'        => $inv->invoice_date,
                'reference'   => $inv->invoice_number ?? $inv->reference_number,
                'description' => 'فاتورة مشتريات',
                'debit'       => 0.0,
                'credit'      => (float) $inv->total_amount,
            ]));

        PurchaseReturn::where('supplier_id', $supplierId)
            ->when($from, fn ($q) => $q->whereDate('return_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('return_date', '<=', $to))
            ->orderBy('return_date')
            ->each(fn ($ret) => $lines->push((object) [
                'date'        => $ret->return_date,
                'reference'   => $ret->reference_number,
                'description' => 'مرتجع مشتريات',
                'debit'       => (float) $ret->total_amount,
                'credit'      => 0.0,
            ]));

        Payment::where('supplier_id', $supplierId)
            ->where('category', 'supplier_payment')
            ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('payment_date', '<=', $to))
            ->orderBy('payment_date')
            ->each(function ($pay) use ($lines) {
                $method = match ($pay->payment_method) {
                    'cash'          => 'كاش',
                    'cheque'        => 'شيك',
                    'bank_transfer' => 'تحويل بنكي',
                    default         => $pay->payment_method,
                };
                $lines->push((object) [
                    'date'        => $pay->payment_date,
                    'reference'   => $pay->payment_number,
                    'description' => "سند صرف ({$method})",
                    'debit'       => (float) $pay->amount,
                    'credit'      => 0.0,
                ]);
            });

        $lines       = $lines->sortBy('date')->values();
        $totalDebit  = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');
        // رصيد المورد: موجب = مستحق له (دائن)
        $closing = $opening + $totalCredit - $totalDebit;

        return PdfService::stream(
            'print.supplier-statement',
            compact('supplier', 'lines', 'opening', 'totalDebit', 'totalCredit', 'closing', 'from', 'to'),
            "statement-{$supplier->code}.pdf"
        );
    }
}
