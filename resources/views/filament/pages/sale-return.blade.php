<x-filament-panels::page>
    @if($invoice)
        <div dir="rtl" style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-family:Cairo,sans-serif; font-size:13px;">
            <strong style="color:#991b1b;">الفاتورة الأصلية:</strong>
            <span style="color:#374151;">
                {{ $invoice->reference_number }} ·
                {{ $invoice->customer->name }} ·
                {{ $invoice->invoice_date->format('d/m/Y') }} ·
                <strong>{{ number_format($invoice->total_amount, 2) }} ج.م</strong>
            </span>
        </div>
        @livewire('sale-return-form', ['invoiceId' => $invoice->id])
    @else
        <div style="text-align:center; padding:48px; color:#9ca3af; font-family:Cairo,sans-serif;">
            لم يتم تحديد فاتورة
        </div>
    @endif
</x-filament-panels::page>
