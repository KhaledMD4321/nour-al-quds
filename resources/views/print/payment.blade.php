@extends('layouts.print-preview')

@php
    $company      = \App\Models\CompanySetting::first();
    $primaryColor = '#dc2626';
    $isSupplier   = $payment->category === 'supplier_payment';
    $isExpense    = !$isSupplier;
    $docLabel     = 'سند صرف — ' . $payment->payment_number;
@endphp

@section('title', $docLabel)
@section('toolbar-title', $docLabel . ($isSupplier && $payment->supplier ? ' | ' . $payment->supplier->name : ''))

@section('styles')
<style>
    body { font-size: 12px; color: #1a1a1a; }

    /* ── الترويسة ── */
    .header {
        display: table; width: 100%;
        border-bottom: 3px solid {{ $primaryColor }};
        padding-bottom: 12px; margin-bottom: 14px;
    }
    .header-right { display: table-cell; vertical-align: top; width: 65%; }
    .header-left  { display: table-cell; vertical-align: top; text-align: left; width: 35%; }
    .company-name { font-size: 18px; font-weight: 800; color: {{ $primaryColor }}; margin-bottom: 3px; }
    .company-sub  { font-size: 10px; color: #6b7280; line-height: 1.7; }
    .logo         { max-height: 60px; max-width: 130px; }

    /* ── شريط عنوان السند ── */
    .doc-title-bar {
        text-align: center;
        background: {{ $primaryColor }}; color: white;
        padding: 7px 12px; border-radius: 6px;
        font-size: 16px; font-weight: 800;
        margin-bottom: 14px;
    }

    /* ── صندوق المبلغ ── */
    .amount-box {
        text-align: center;
        background: linear-gradient(135deg, {{ $primaryColor }}, #ef4444);
        color: white; border-radius: 10px;
        padding: 18px 20px; margin-bottom: 14px;
    }
    .amount-label    { font-size: 11px; opacity: .85; margin-bottom: 5px; }
    .amount-value    { font-size: 34px; font-weight: 800; line-height: 1.1; }
    .amount-currency { font-size: 15px; opacity: .75; margin-right: 4px; }
    .method-badge {
        display: inline-block; padding: 3px 14px;
        border-radius: 999px; font-size: 11px; font-weight: 600;
        margin-top: 6px; background: rgba(255,255,255,0.25); color: white;
    }
    .category-badge {
        display: inline-block; padding: 3px 12px;
        border-radius: 999px; font-size: 10px; font-weight: 600;
        background: rgba(255,255,255,0.2); color: white; margin-top: 4px;
    }

    /* ── جدول البيانات ── */
    .info-table {
        width: 100%; border-collapse: collapse;
        margin-bottom: 14px; border: 1px solid #e5e7eb;
        border-radius: 6px; overflow: hidden;
    }
    .info-table tr:nth-child(even) { background: #fff5f5; }
    .info-table td {
        padding: 7px 12px; font-size: 11.5px;
        border-bottom: 1px solid #f3f4f6; vertical-align: top;
    }
    .info-table td:first-child {
        color: #6b7280; width: 38%;
        font-size: 10.5px; font-weight: 600; white-space: nowrap;
    }
    .info-table td:last-child { color: #111827; font-weight: 600; }
    .info-ref { font-size: 13px; color: {{ $primaryColor }}; font-weight: 800; }

    /* ── بيانات الشيك ── */
    .cheque-box {
        border: 1px solid #fde68a; background: #fffbeb;
        border-radius: 8px; padding: 12px 16px; margin-bottom: 14px;
    }
    .cheque-title { font-weight: 700; color: #92400e; margin-bottom: 8px; font-size: 12px; }
    .cheque-grid  { display: table; width: 100%; }
    .cheque-item  { display: table-cell; width: 33%; padding: 0 4px; }
    .cheque-item-label { font-size: 10px; color: #92400e; }
    .cheque-item-value { font-size: 12px; font-weight: 700; color: #78350f; margin-top: 2px; }

    /* ── ملاحظات ── */
    .notes-box {
        border: 1px solid #fecaca; background: #fff5f5;
        border-radius: 6px; padding: 10px 14px;
        margin-bottom: 14px; font-size: 11px; color: #374151;
    }
    .notes-title { font-size: 10px; font-weight: 700; color: #9ca3af; margin-bottom: 3px; }

    /* ── التوقيعات ── */
    .sig-wrap { display: table; width: 100%; margin-top: 28px; margin-bottom: 16px; }
    .sig-cell { display: table-cell; width: 50%; text-align: center; padding: 0 18px; }
    .sig-line {
        border-top: 1px solid #374151; padding-top: 5px;
        font-size: 11px; font-weight: 600; margin-top: 32px;
    }

    /* ── التذييل ── */
    .doc-footer {
        text-align: center; font-size: 10px; color: #9ca3af;
        border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 8px;
    }
</style>
@endsection

@section('content')

    {{-- ══ الترويسة ══ --}}
    <div class="header">
        <div class="header-right">
            <div class="company-name">{{ $company?->name ?? \App\Models\SystemSetting::get('company.name', 'نور القدس') }}</div>
            <div class="company-sub">
                @if($company?->address){{ $company->address }}<br>@endif
                @if($company?->phone)هاتف: {{ $company->phone }}<br>@endif
                @if($company?->tax_number)البطاقة الضريبية: {{ $company->tax_number }}@endif
            </div>
        </div>
        <div class="header-left">
            @if($company?->logo)
                <img src="{{ asset('storage/' . $company->logo) }}" class="logo" alt="">
            @endif
        </div>
    </div>

    {{-- ══ شريط عنوان السند ══ --}}
    <div class="doc-title-bar">سند صرف</div>

    {{-- ══ صندوق المبلغ ══ --}}
    <div class="amount-box">
        <div class="amount-label">المبلغ المصروف</div>
        <div class="amount-value">
            {{ number_format((float) $payment->amount, 2) }}
            <span class="amount-currency">ج.م</span>
        </div>
        <div>
            <span class="method-badge">
                {{ match($payment->payment_method) {
                    'cash'          => 'كاش',
                    'bank_transfer' => 'تحويل بنكي',
                    'cheque'        => 'شيك',
                    default         => $payment->payment_method,
                } }}
            </span>
            <span class="category-badge">{{ $payment->category_label }}</span>
        </div>
    </div>

    {{-- ══ جدول البيانات ══ --}}
    <table class="info-table">
        <tr>
            <td>رقم السند</td>
            <td><span class="info-ref">{{ $payment->payment_number }}</span></td>
        </tr>
        <tr>
            <td>التاريخ</td>
            <td>{{ $payment->payment_date?->format('d/m/Y') }}</td>
        </tr>

        @if($isSupplier && $payment->supplier)
        <tr>
            <td>المورد</td>
            <td>
                {{ $payment->supplier->name }}
                @if($payment->supplier->code)
                    <span style="color:#9ca3af; font-size:10px; font-weight:400;">
                        ({{ $payment->supplier->code }})
                    </span>
                @endif
            </td>
        </tr>
        @if($payment->supplier->phone)
        <tr>
            <td>هاتف المورد</td>
            <td>{{ $payment->supplier->phone }}</td>
        </tr>
        @endif
        @endif

        @if($isExpense)
        <tr>
            <td>تصنيف المصروف</td>
            <td>{{ $payment->category_label }}</td>
        </tr>
        @if($payment->expenseAccount)
        <tr>
            <td>حساب المصروف</td>
            <td>{{ $payment->expenseAccount->code }} — {{ $payment->expenseAccount->name }}</td>
        </tr>
        @endif
        @endif

        @if($payment->purchaseInvoice)
        <tr>
            <td>فاتورة الشراء</td>
            <td>{{ $payment->purchaseInvoice->reference_number }}</td>
        </tr>
        <tr>
            <td>المتبقي على الفاتورة</td>
            <td style="{{ $payment->purchaseInvoice->remaining_amount > 0 ? 'color:#dc2626;' : 'color:#16a34a;' }}">
                {{ number_format($payment->purchaseInvoice->remaining_amount, 2) }} ج.م
            </td>
        </tr>
        @endif

        @if($payment->treasury)
        <tr>
            <td>الخزينة</td>
            <td>{{ $payment->treasury->name }}</td>
        </tr>
        @endif
        <tr>
            <td>الوحدة التشغيلية</td>
            <td>{{ $payment->businessUnit?->name }}</td>
        </tr>
        @if($payment->bank_reference)
        <tr>
            <td>مرجع التحويل</td>
            <td>{{ $payment->bank_reference }}</td>
        </tr>
        @endif
        @if($payment->journalEntry)
        <tr>
            <td>القيد المحاسبي</td>
            <td>{{ $payment->journalEntry->entry_number }}</td>
        </tr>
        @endif
        @if($payment->createdBy)
        <tr>
            <td>أنشأه</td>
            <td>{{ $payment->createdBy->name }}</td>
        </tr>
        @endif
    </table>

    {{-- ══ بيانات الشيك ══ --}}
    @if($payment->payment_method === 'cheque' && $payment->cheque_details)
        <div class="cheque-box">
            <div class="cheque-title">📋 بيانات الشيك</div>
            <div class="cheque-grid">
                <div class="cheque-item">
                    <div class="cheque-item-label">رقم الشيك</div>
                    <div class="cheque-item-value">{{ $payment->cheque_details['cheque_number'] ?? '—' }}</div>
                </div>
                <div class="cheque-item">
                    <div class="cheque-item-label">اسم البنك</div>
                    <div class="cheque-item-value">{{ $payment->cheque_details['bank_name'] ?? '—' }}</div>
                </div>
                <div class="cheque-item">
                    <div class="cheque-item-label">تاريخ الاستحقاق</div>
                    <div class="cheque-item-value">{{ $payment->cheque_details['due_date'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══ ملاحظات ══ --}}
    @if($payment->notes)
        <div class="notes-box">
            <div class="notes-title">ملاحظات:</div>
            {{ $payment->notes }}
        </div>
    @endif

    {{-- ══ التوقيعات ══ --}}
    <div class="sig-wrap">
        <div class="sig-cell">
            <div class="sig-line">المُحرِّر</div>
        </div>
        <div class="sig-cell">
            <div class="sig-line">المستلم</div>
        </div>
    </div>

    {{-- ══ التذييل ══ --}}
    <div class="doc-footer">
        {{ $company?->name ?? \App\Models\SystemSetting::get('company.name', 'نور القدس') }}
        @if($company?->phone) | هاتف: {{ $company->phone }}@endif
        | {{ $payment->payment_number }}
        — {{ $payment->payment_date?->format('d/m/Y') }}
    </div>

@endsection
