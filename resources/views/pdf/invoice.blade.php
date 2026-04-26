<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            padding: 28px 32px;
            font-size: 12px;
            color: #1a1a1a;
            direction: rtl;
        }
        /* ── Header ── */
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #1d4ed8;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            width: 60%;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
            text-align: left;
            width: 40%;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1d4ed8;
        }
        .company-sub {
            font-size: 10px;
            color: #555;
            margin-top: 4px;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .invoice-number {
            font-size: 13px;
            color: #374151;
            margin-top: 4px;
        }
        .status-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            background: #dcfce7;
            color: #166534;
            margin-top: 4px;
        }
        /* ── Info Boxes ── */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 11px;
            vertical-align: top;
        }
        .info-box:first-child {
            margin-left: 8px;
        }
        .info-box-title {
            font-size: 10px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 4px;
        }
        .info-row {
            margin-bottom: 3px;
            color: #374151;
        }
        .info-label {
            color: #9ca3af;
            font-size: 10px;
        }
        /* ── Table ── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11px;
        }
        table.items th {
            background: #1d4ed8;
            color: #fff;
            padding: 7px 8px;
            text-align: right;
            font-weight: bold;
            border: none;
        }
        table.items td {
            padding: 6px 8px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }
        table.items tr:nth-child(even) td {
            background: #f9fafb;
        }
        .num {
            text-align: center;
        }
        /* ── Totals ── */
        .totals {
            display: table;
            width: 340px;
            margin-right: auto;
            margin-left: 0;
            margin-bottom: 20px;
        }
        .totals-row {
            display: table-row;
        }
        .totals-label {
            display: table-cell;
            padding: 4px 8px;
            font-size: 11px;
            color: #6b7280;
            text-align: right;
        }
        .totals-value {
            display: table-cell;
            padding: 4px 8px;
            font-size: 11px;
            color: #1a1a1a;
            text-align: left;
            min-width: 120px;
        }
        .totals-grand .totals-label,
        .totals-grand .totals-value {
            font-size: 14px;
            font-weight: bold;
            color: #1d4ed8;
            border-top: 2px solid #1d4ed8;
            padding-top: 6px;
        }
        .totals-paid .totals-label,
        .totals-paid .totals-value {
            color: #16a34a;
        }
        .totals-remaining .totals-label,
        .totals-remaining .totals-value {
            color: #dc2626;
            font-weight: bold;
        }
        /* ── Notes & Footer ── */
        .notes {
            font-size: 10px;
            color: #555;
            margin-bottom: 12px;
            padding: 8px 10px;
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-right">
            <div class="company-name">{{ $company?->name ?? 'نور القدس' }}</div>
            @if($company?->address)
                <div class="company-sub">{{ $company->address }}</div>
            @endif
            @if($company?->phone)
                <div class="company-sub">هاتف: {{ $company->phone }}</div>
            @endif
        </div>
        <div class="header-left">
            <div class="invoice-title">فاتورة بيع</div>
            <div class="invoice-number">رقم: <strong>{{ $invoice->reference_number }}</strong></div>
            <div class="status-badge">{{ $invoice->status_label }}</div>
        </div>
    </div>

    {{-- Info Boxes: بيانات العميل + بيانات الفاتورة --}}
    <div class="info-section">
        <div class="info-box">
            <div class="info-box-title">بيانات العميل</div>
            <div class="info-row"><strong>{{ $invoice->customer->name }}</strong></div>
            <div class="info-row"><span class="info-label">كود: </span>{{ $invoice->customer->code }}</div>
            @if($invoice->customer->phone)
                <div class="info-row"><span class="info-label">هاتف: </span>{{ $invoice->customer->phone }}</div>
            @endif
            @if($invoice->customer->address)
                <div class="info-row"><span class="info-label">عنوان: </span>{{ $invoice->customer->address }}</div>
            @endif
        </div>
        <div class="info-box" style="margin-right:8px;">
            <div class="info-box-title">بيانات الفاتورة</div>
            <div class="info-row"><span class="info-label">التاريخ: </span>{{ $invoice->invoice_date->format('d/m/Y') }}</div>
            @if($invoice->due_date)
                <div class="info-row"><span class="info-label">الاستحقاق: </span>{{ $invoice->due_date->format('d/m/Y') }}</div>
            @endif
            <div class="info-row"><span class="info-label">طريقة الدفع: </span>
                {{ match($invoice->payment_type) { 'cash'=>'نقدي','credit'=>'آجل','cheque'=>'شيك',default=>$invoice->payment_type } }}
            </div>
            <div class="info-row"><span class="info-label">الوحدة التشغيلية: </span>{{ $invoice->businessUnit->name }}</div>
            <div class="info-row"><span class="info-label">المخزن: </span>{{ $invoice->warehouse->name }}</div>
            @if($invoice->createdBy)
                <div class="info-row"><span class="info-label">المحرر: </span>{{ $invoice->createdBy->name }}</div>
            @endif
        </div>
    </div>

    {{-- البنود --}}
    <table class="items">
        <thead>
            <tr>
                <th class="num" style="width:4%">#</th>
                <th>الصنف</th>
                <th class="num" style="width:9%">الكمية</th>
                <th class="num" style="width:14%">سعر اللستة</th>
                <th class="num" style="width:8%">خ1%</th>
                <th class="num" style="width:8%">خ2%</th>
                <th class="num" style="width:8%">خ3%</th>
                <th class="num" style="width:13%">سعر الوحدة</th>
                <th class="num" style="width:14%">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td class="num">{{ number_format($item->quantity, 2) }}</td>
                    <td class="num">{{ number_format($item->list_price, 2) }}</td>
                    <td class="num">{{ number_format($item->discount_1, 1) }}%</td>
                    <td class="num">{{ number_format($item->discount_2, 1) }}%</td>
                    <td class="num">{{ number_format($item->discount_3, 1) }}%</td>
                    <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="num" style="font-weight:bold;">{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- الإجماليات --}}
    <div class="totals">
        <div class="totals-row">
            <div class="totals-label">إجمالي البضاعة</div>
            <div class="totals-value">{{ number_format($invoice->subtotal, 2) }} ج.م</div>
        </div>
        @if((float)$invoice->discount_amount > 0)
            <div class="totals-row">
                <div class="totals-label">خصم إضافي</div>
                <div class="totals-value">( {{ number_format($invoice->discount_amount, 2) }} ) ج.م</div>
            </div>
        @endif
        @if((float)$invoice->tax_amount > 0)
            <div class="totals-row">
                <div class="totals-label">ضريبة</div>
                <div class="totals-value">{{ number_format($invoice->tax_amount, 2) }} ج.م</div>
            </div>
        @endif
        <div class="totals-row totals-grand">
            <div class="totals-label">الإجمالي الكلي</div>
            <div class="totals-value">{{ number_format($invoice->total_amount, 2) }} ج.م</div>
        </div>
        @if((float)$invoice->paid_amount > 0)
            <div class="totals-row totals-paid">
                <div class="totals-label">المدفوع</div>
                <div class="totals-value">{{ number_format($invoice->paid_amount, 2) }} ج.م</div>
            </div>
        @endif
        @if($invoice->remaining_amount > 0)
            <div class="totals-row totals-remaining">
                <div class="totals-label">المتبقي</div>
                <div class="totals-value">{{ number_format($invoice->remaining_amount, 2) }} ج.م</div>
            </div>
        @endif
    </div>

    {{-- ملاحظات --}}
    @if($invoice->notes)
        <div class="notes">ملاحظات: {{ $invoice->notes }}</div>
    @endif

    <div class="footer">
        شكراً لتعاملكم معنا
        @if($company?->phone) ● {{ $company->phone }} @endif
        @if($company?->email) ● {{ $company->email }} @endif
    </div>

</body>
</html>
