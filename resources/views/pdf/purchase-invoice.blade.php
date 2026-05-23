<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>فاتورة مشتريات {{ $invoice->reference_number }}</title>

    @php
        $primaryColor = '#b45309';          // برتقالي ثابت لفواتير الشراء
        $showSig      = (bool) \App\Models\SystemSetting::get('invoice.show_signature', true);
        $footerNote   = \App\Models\SystemSetting::get('invoice.footer_note', '');
    @endphp

    <style>
        @page { size: A4; margin: 12mm 14mm 14mm 14mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'xbriyaz', sans-serif;
            direction: rtl;
            color: #1f2937;
            font-size: 11.5px;
            line-height: 1.5;
        }

        /* ─── الترويسة ─── */
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid {{ $primaryColor }};
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .header-right { display: table-cell; vertical-align: top; width: 60%; }
        .header-left  { display: table-cell; vertical-align: top; text-align: left; width: 40%; }
        .company-name {
            font-size: 19px;
            font-weight: bold;
            color: {{ $primaryColor }};
            margin-bottom: 4px;
        }
        .company-sub  { font-size: 10px; color: #6b7280; line-height: 1.7; }
        .logo         { max-height: 68px; max-width: 145px; }

        /* ─── شريط العنوان ─── */
        .invoice-title-bar {
            text-align: center;
            background: {{ $primaryColor }};
            color: white;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 13px;
            letter-spacing: 0.5px;
        }

        /* ─── صناديق البيانات ─── */
        .info-section { display: table; width: 100%; margin-bottom: 13px; }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 9px 11px;
            border: 1px solid #e5e7eb;
        }
        .info-box:first-child { border-radius: 6px 0 0 6px; border-left: none; }
        .info-box:last-child  { border-radius: 0 6px 6px 0; }
        .info-box-title {
            font-size: 9px;
            font-weight: bold;
            color: #9ca3af;
            text-transform: uppercase;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }
        .info-row   { margin-bottom: 3px; font-size: 11px; color: #374151; }
        .info-label { color: #9ca3af; font-size: 10px; }
        .info-val   { font-weight: 600; color: #111827; }

        /* ─── جدول البنود ─── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 13px;
            font-size: 10.5px;
        }
        table.items thead { background: {{ $primaryColor }}; color: #fff; }
        table.items th {
            padding: 7px 8px;
            text-align: right;
            font-weight: 600;
            font-size: 10px;
        }
        table.items th.c, table.items td.c { text-align: center; }
        table.items th.l, table.items td.l { text-align: left; }
        table.items td {
            padding: 6px 8px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }
        table.items tbody tr:nth-child(even) td { background: #fdf7ef; }

        /* ─── الإجماليات ─── */
        .totals-wrap  { display: table; width: 100%; margin-bottom: 13px; }
        .totals-space { display: table-cell; width: 52%; }
        .totals-box   {
            display: table-cell;
            width: 48%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }
        .t-row        { display: table; width: 100%; }
        .t-row > div  { display: table-cell; padding: 5px 11px; font-size: 11px; }
        .t-row > div:first-child { text-align: right; color: #4b5563; font-weight: 600; width: 55%; }
        .t-row > div:last-child  { text-align: left;  color: #111827; font-weight: 600; }
        .t-grand      { background: {{ $primaryColor }}; }
        .t-grand > div { color: white !important; font-size: 13px !important; font-weight: 800 !important; padding: 8px 11px !important; }
        .t-paid  > div { color: #059669 !important; }
        .t-rem   > div { color: #dc2626 !important; background: #fef2f2; font-weight: 800 !important; }

        /* ─── ملاحظات ─── */
        .notes-box {
            background: #fffbf5;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 9px 12px;
            margin-bottom: 13px;
            font-size: 10px;
            color: #4b5563;
            line-height: 1.7;
        }
        .notes-title { font-weight: bold; color: #374151; margin-bottom: 2px; }

        /* ─── التوقيعات ─── */
        .sig-wrap { display: table; width: 100%; margin-top: 32px; }
        .sig-cell {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 0 14px;
        }
        .sig-line {
            border-top: 1px solid #374151;
            padding-top: 5px;
            font-size: 10.5px;
            font-weight: 600;
            margin-top: 36px;
        }
        .sig-name { font-size: 9px; color: #9ca3af; margin-top: 2px; }

        /* ─── التذييل الثابت ─── */
        .footer {
            position: fixed;
            bottom: 8mm;
            right: 14mm;
            left: 14mm;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }

        /* ─── شارة الحالة ─── */
        .status-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    {{-- ═══ الترويسة ═══ --}}
    <div class="header">
        <div class="header-right">
            <div class="company-name">{{ $company?->name ?? 'نور القدس' }}</div>
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

    {{-- ═══ شريط النوع ═══ --}}
    <div class="invoice-title-bar">فاتورة مشتريات</div>

    {{-- ═══ صناديق البيانات ═══ --}}
    <div class="info-section">
        {{-- بيانات المورد --}}
        <div class="info-box">
            <div class="info-box-title">بيانات المورد</div>
            <div class="info-row"><span class="info-val">{{ $invoice->supplier->name }}</span></div>
            @if($invoice->supplier->code)
                <div class="info-row"><span class="info-label">كود: </span>{{ $invoice->supplier->code }}</div>
            @endif
            @if($invoice->supplier->phone)
                <div class="info-row"><span class="info-label">هاتف: </span>{{ $invoice->supplier->phone }}</div>
            @endif
            @if($invoice->supplier->address)
                <div class="info-row"><span class="info-label">عنوان: </span>{{ $invoice->supplier->address }}</div>
            @endif
            @if($invoice->supplier->tax_registration_number)
                <div class="info-row"><span class="info-label">ضريبي: </span>{{ $invoice->supplier->tax_registration_number }}</div>
            @endif
        </div>

        {{-- بيانات الفاتورة --}}
        <div class="info-box">
            <div class="info-box-title">بيانات الفاتورة</div>
            <div class="info-row">
                <span class="info-label">رقم الفاتورة: </span>
                <span style="font-family: monospace; font-weight: 800; color: {{ $primaryColor }};">{{ $invoice->reference_number }}</span>
            </div>
            @if($invoice->invoice_number)
                <div class="info-row"><span class="info-label">رقم فاتورة المورد: </span>{{ $invoice->invoice_number }}</div>
            @endif
            <div class="info-row"><span class="info-label">تاريخ الفاتورة: </span>{{ $invoice->invoice_date->format('d/m/Y') }}</div>
            @if($invoice->due_date)
                <div class="info-row"><span class="info-label">تاريخ الاستحقاق: </span>{{ $invoice->due_date->format('d/m/Y') }}</div>
            @endif
            <div class="info-row">
                <span class="info-label">الحالة: </span>
                <span class="status-badge" style="background: {{ $invoice->status === 'paid' ? '#dcfce7' : ($invoice->status === 'confirmed' ? '#dbeafe' : '#f3f4f6') }};
                    color: {{ $invoice->status === 'paid' ? '#166534' : ($invoice->status === 'confirmed' ? '#1e40af' : '#4b5563') }};">
                    {{ $invoice->status_label }}
                </span>
            </div>
            <div class="info-row"><span class="info-label">المخزن: </span>{{ $invoice->warehouse->name }}</div>
            <div class="info-row"><span class="info-label">الوحدة التشغيلية: </span>{{ $invoice->businessUnit->name }}</div>
            @if($invoice->createdBy)
                <div class="info-row"><span class="info-label">المحرّر: </span>{{ $invoice->createdBy->name }}</div>
            @endif
        </div>
    </div>

    {{-- ═══ جدول البنود ═══ --}}
    <table class="items">
        <thead>
            <tr>
                <th class="c" style="width: 28px;">#</th>
                <th>الصنف</th>
                <th class="c" style="width: 60px;">الكمية</th>
                <th class="c" style="width: 80px;">سعر الشراء</th>
                <th class="l" style="width: 88px;">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>
                        <span style="font-weight: 600;">{{ $item->product->name }}</span>
                    </td>
                    <td class="c">{{ number_format($item->quantity, 2) }}</td>
                    <td class="c">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="l" style="font-weight: 700;">{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ═══ الإجماليات ═══ --}}
    <div class="totals-wrap">
        <div class="totals-space"></div>
        <div class="totals-box">
            <div class="t-row">
                <div>إجمالي البنود</div>
                <div>{{ number_format($invoice->subtotal, 2) }} ج.م</div>
            </div>
            @if((float) ($invoice->tax_amount ?? 0) > 0)
                <div class="t-row">
                    <div>ضريبة</div>
                    <div>{{ number_format($invoice->tax_amount, 2) }} ج.م</div>
                </div>
            @endif
            @if((float) ($invoice->total_landed_cost ?? 0) > 0)
                <div class="t-row">
                    <div>تكاليف شحن وتخليص</div>
                    <div>{{ number_format($invoice->total_landed_cost, 2) }} ج.م</div>
                </div>
            @endif
            <div class="t-row t-grand">
                <div>إجمالي الفاتورة</div>
                <div>{{ number_format($invoice->total_amount, 2) }} ج.م</div>
            </div>
            @if((float) $invoice->paid_amount > 0)
                <div class="t-row t-paid">
                    <div>المدفوع</div>
                    <div>{{ number_format($invoice->paid_amount, 2) }} ج.م</div>
                </div>
                @if($invoice->remaining_amount > 0)
                    <div class="t-row t-rem">
                        <div>المتبقي</div>
                        <div>{{ number_format($invoice->remaining_amount, 2) }} ج.م</div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- ═══ ملاحظات ═══ --}}
    @if($invoice->notes)
        <div class="notes-box">
            <div class="notes-title">ملاحظات:</div>
            {{ $invoice->notes }}
        </div>
    @endif

    @if($footerNote)
        <div class="notes-box" style="border-color: #e5e7eb; background: #f9fafb;">
            {{ $footerNote }}
        </div>
    @endif

    {{-- ═══ التوقيعات ═══ --}}
    @if($showSig)
        <div class="sig-wrap">
            <div class="sig-cell">
                <div class="sig-line">المستلم</div>
            </div>
            <div class="sig-cell">
                <div class="sig-line">أمين المخزن</div>
            </div>
            <div class="sig-cell">
                <div class="sig-line">المدير</div>
            </div>
        </div>
    @endif

    {{-- ═══ التذييل الثابت ═══ --}}
    <div class="footer">
        {{ $company?->name ?? 'نور القدس' }}
        @if($company?->phone) | هاتف: {{ $company->phone }}@endif
        @if($company?->tax_number) | ضريبي: {{ $company->tax_number }}@endif
        | {{ $invoice->reference_number }}
        — {{ $invoice->invoice_date->format('d/m/Y') }}
    </div>

</body>
</html>
