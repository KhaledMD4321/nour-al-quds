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
            border-bottom: 3px solid #7c3aed;
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
            color: #7c3aed;
        }
        .company-sub {
            font-size: 10px;
            color: #555;
            margin-top: 4px;
        }
        .doc-title {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .doc-number {
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
            background: #f3e8ff;
            color: #6d28d9;
            margin-top: 4px;
        }
        /* ── Validity Banner ── */
        .validity-banner {
            background: #faf5ff;
            border: 1px solid #c4b5fd;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 16px;
            font-size: 10px;
            color: #5b21b6;
            text-align: center;
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
            color: #7c3aed;
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
            background: #7c3aed;
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
            background: #faf5ff;
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
            color: #7c3aed;
            border-top: 2px solid #7c3aed;
            padding-top: 6px;
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
        .not-invoice-notice {
            text-align: center;
            font-size: 9px;
            color: #7c3aed;
            margin-top: 8px;
            font-style: italic;
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
            <div class="doc-title">عرض سعر</div>
            <div class="doc-number">رقم: <strong>{{ $quotation->reference_number }}</strong></div>
            <div class="doc-number">التاريخ: {{ $quotation->invoice_date->format('d/m/Y') }}</div>
            <div class="status-badge">
                {{ $quotation->status === 'cancelled' ? 'ملغى' : 'ساري' }}
            </div>
        </div>
    </div>

    {{-- Validity Notice --}}
    <div class="validity-banner">
        ⚠️ هذا المستند عرض سعر وليس فاتورة رسمية — الأسعار سارية لمدة 30 يوماً من تاريخ الإصدار
    </div>

    {{-- Info Boxes: بيانات العميل + بيانات العرض --}}
    <div class="info-section">
        <div class="info-box">
            <div class="info-box-title">بيانات العميل</div>
            <div class="info-row"><strong>{{ $quotation->customer->name }}</strong></div>
            <div class="info-row"><span class="info-label">كود: </span>{{ $quotation->customer->code }}</div>
            @if($quotation->customer->phone)
                <div class="info-row"><span class="info-label">هاتف: </span>{{ $quotation->customer->phone }}</div>
            @endif
            @if($quotation->customer->address)
                <div class="info-row"><span class="info-label">عنوان: </span>{{ $quotation->customer->address }}</div>
            @endif
        </div>
        <div class="info-box" style="margin-right:8px;">
            <div class="info-box-title">بيانات العرض</div>
            <div class="info-row"><span class="info-label">رقم العرض: </span>{{ $quotation->reference_number }}</div>
            <div class="info-row"><span class="info-label">التاريخ: </span>{{ $quotation->invoice_date->format('d/m/Y') }}</div>
            <div class="info-row"><span class="info-label">صلاحية العرض: </span>30 يوماً</div>
            <div class="info-row"><span class="info-label">طريقة الدفع: </span>
                {{ match($quotation->payment_type) { 'cash'=>'نقدي','credit'=>'آجل','cheque'=>'شيك',default=>$quotation->payment_type } }}
            </div>
            <div class="info-row"><span class="info-label">الوحدة التشغيلية: </span>{{ $quotation->businessUnit->name }}</div>
            @if($quotation->createdBy)
                <div class="info-row"><span class="info-label">المحرر: </span>{{ $quotation->createdBy->name }}</div>
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
            @foreach($quotation->items as $i => $item)
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
            <div class="totals-value">{{ number_format($quotation->subtotal, 2) }} ج.م</div>
        </div>
        @if((float)$quotation->discount_amount > 0)
            <div class="totals-row">
                <div class="totals-label">إجمالي الخصم</div>
                <div class="totals-value">( {{ number_format($quotation->discount_amount, 2) }} ) ج.م</div>
            </div>
        @endif
        <div class="totals-row totals-grand">
            <div class="totals-label">إجمالي العرض</div>
            <div class="totals-value">{{ number_format($quotation->total_amount, 2) }} ج.م</div>
        </div>
    </div>

    {{-- ملاحظات --}}
    @if($quotation->notes)
        <div class="notes">ملاحظات: {{ $quotation->notes }}</div>
    @endif

    <div class="footer">
        شكراً لاهتمامكم — يسعدنا خدمتكم
        @if($company?->phone) ● {{ $company->phone }} @endif
        @if($company?->email) ● {{ $company->email }} @endif
    </div>

    <div class="not-invoice-notice">
        * هذا العرض لا يُعدّ فاتورة رسمية ولا يُرتّب أي التزام قانوني حتى يتم إصدار فاتورة رسمية معتمدة
    </div>

</body>
</html>
