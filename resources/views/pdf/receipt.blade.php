<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سند قبض — {{ $receipt->receipt_number }}</title>

    @php
        $company      = \App\Models\CompanySetting::first();
        $primaryColor = '#1e40af';
    @endphp

    <style>
        @page { size: A4; margin: 12mm 14mm 14mm 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'xbriyaz', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            direction: rtl;
            background: #fff;
        }

        .header {
            display: table; width: 100%;
            border-bottom: 3px solid {{ $primaryColor }};
            padding-bottom: 12px; margin-bottom: 14px;
        }
        .header-right { display: table-cell; vertical-align: top; width: 65%; }
        .header-left  { display: table-cell; vertical-align: top; text-align: left; width: 35%; }
        .company-name { font-size: 18px; font-weight: bold; color: {{ $primaryColor }}; margin-bottom: 3px; }
        .company-sub  { font-size: 10px; color: #6b7280; line-height: 1.7; }
        .logo         { max-height: 60px; max-width: 130px; }

        .doc-title-bar {
            text-align: center;
            background: {{ $primaryColor }}; color: white;
            padding: 7px 12px; border-radius: 6px;
            font-size: 16px; font-weight: bold;
            margin-bottom: 14px;
        }

        .amount-box {
            text-align: center;
            background: linear-gradient(135deg, {{ $primaryColor }}, #3b82f6);
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

        .info-table {
            width: 100%; border-collapse: collapse;
            margin-bottom: 14px; border: 1px solid #e5e7eb;
            border-radius: 6px; overflow: hidden;
        }
        .info-table tr:nth-child(even) { background: #f9fafb; }
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

        .cheque-box {
            border: 1px solid #fde68a; background: #fffbeb;
            border-radius: 8px; padding: 12px 16px; margin-bottom: 14px;
        }
        .cheque-title { font-weight: 700; color: #92400e; margin-bottom: 8px; font-size: 12px; }
        .cheque-grid  { display: table; width: 100%; }
        .cheque-item  { display: table-cell; width: 33%; padding: 0 4px; }
        .cheque-item-label { font-size: 10px; color: #92400e; }
        .cheque-item-value { font-size: 12px; font-weight: 700; color: #78350f; margin-top: 2px; }

        .notes-box {
            border: 1px solid #e5e7eb; background: #f9fafb;
            border-radius: 6px; padding: 10px 14px;
            margin-bottom: 14px; font-size: 11px; color: #374151;
        }
        .notes-title { font-size: 10px; font-weight: 700; color: #6b7280; margin-bottom: 3px; }

        .sig-wrap { display: table; width: 100%; margin-top: 28px; margin-bottom: 16px; }
        .sig-cell { display: table-cell; width: 50%; text-align: center; padding: 0 18px; }
        .sig-line {
            border-top: 1px solid #374151; padding-top: 5px;
            font-size: 11px; font-weight: 600; margin-top: 32px;
        }

        .footer {
            text-align: center; font-size: 10px; color: #9ca3af;
            border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 8px;
        }
    </style>
</head>
<body>

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
                <img src="{{ public_path('storage/' . $company->logo) }}" class="logo" alt="">
            @endif
        </div>
    </div>

    <div class="doc-title-bar">سند قبض</div>

    <div class="amount-box">
        <div class="amount-label">المبلغ المستلم</div>
        <div class="amount-value">
            {{ number_format((float) $receipt->amount, 2) }}
            <span class="amount-currency">ج.م</span>
        </div>
        <div>
            <span class="method-badge">{{ $receipt->payment_method_label }}</span>
        </div>
    </div>

    <table class="info-table">
        <tr><td>رقم السند</td><td><span class="info-ref">{{ $receipt->receipt_number }}</span></td></tr>
        <tr><td>التاريخ</td><td>{{ $receipt->receipt_date?->format('d/m/Y') }}</td></tr>
        <tr>
            <td>العميل</td>
            <td>{{ $receipt->customer?->name }}
                @if($receipt->customer?->code)
                    <span style="color:#9ca3af; font-size:10px; font-weight:400;">({{ $receipt->customer->code }})</span>
                @endif
            </td>
        </tr>
        @if($receipt->customer?->phone)
        <tr><td>هاتف العميل</td><td>{{ $receipt->customer->phone }}</td></tr>
        @endif
        @if($receipt->treasury)
        <tr><td>الخزينة</td><td>{{ $receipt->treasury->name }}</td></tr>
        @endif
        <tr><td>الوحدة التشغيلية</td><td>{{ $receipt->businessUnit?->name }}</td></tr>
        @if($receipt->invoice)
        <tr><td>الفاتورة المسددة</td><td>{{ $receipt->invoice->reference_number }}</td></tr>
        <tr>
            <td>المتبقي على الفاتورة</td>
            <td style="{{ $receipt->invoice->remaining_amount > 0 ? 'color:#dc2626;' : 'color:#16a34a;' }}">
                {{ number_format($receipt->invoice->remaining_amount, 2) }} ج.م
            </td>
        </tr>
        @endif
        @if($receipt->bank_reference)
        <tr><td>مرجع التحويل</td><td>{{ $receipt->bank_reference }}</td></tr>
        @endif
        @if($receipt->journalEntry)
        <tr><td>القيد المحاسبي</td><td>{{ $receipt->journalEntry->entry_number }}</td></tr>
        @endif
        @if($receipt->createdBy)
        <tr><td>أنشأه</td><td>{{ $receipt->createdBy->name }}</td></tr>
        @endif
    </table>

    @if($receipt->payment_method === 'cheque' && $receipt->cheque_details)
        <div class="cheque-box">
            <div class="cheque-title">بيانات الشيك</div>
            <div class="cheque-grid">
                <div class="cheque-item">
                    <div class="cheque-item-label">رقم الشيك</div>
                    <div class="cheque-item-value">{{ $receipt->cheque_details['cheque_number'] ?? '—' }}</div>
                </div>
                <div class="cheque-item">
                    <div class="cheque-item-label">اسم البنك</div>
                    <div class="cheque-item-value">{{ $receipt->cheque_details['bank_name'] ?? '—' }}</div>
                </div>
                <div class="cheque-item">
                    <div class="cheque-item-label">تاريخ الاستحقاق</div>
                    <div class="cheque-item-value">{{ $receipt->cheque_details['due_date'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    @endif

    @if($receipt->notes)
        <div class="notes-box">
            <div class="notes-title">ملاحظات:</div>
            {{ $receipt->notes }}
        </div>
    @endif

    <div class="sig-wrap">
        <div class="sig-cell"><div class="sig-line">المُحصِّل</div></div>
        <div class="sig-cell"><div class="sig-line">العميل</div></div>
    </div>

    <div class="footer">
        {{ $company?->name ?? \App\Models\SystemSetting::get('company.name', 'نور القدس') }}
        @if($company?->phone) | هاتف: {{ $company->phone }}@endif
        | {{ $receipt->receipt_number }} — {{ $receipt->receipt_date?->format('d/m/Y') }}
    </div>

</body>
</html>
