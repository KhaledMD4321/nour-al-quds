<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إيصال تحصيل — {{ $receipt->receipt_number }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            padding: 30px;
            font-size: 13px;
            color: #1a1a1a;
            direction: rtl;
            background: #fff;
        }
        @media print {
            body { padding: 10mm; }
            .no-print { display: none !important; }
        }

        /* ── Header ── */
        .header {
            text-align: center;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }
        .company-name  { font-size: 22px; font-weight: bold; color: #1e40af; }
        .company-sub   { font-size: 11px; color: #555; margin-top: 4px; }
        .doc-title     { font-size: 16px; font-weight: bold; color: #374151; margin-top: 8px; letter-spacing: 1px; }

        /* ── Info grid ── */
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 24px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            background: #f9fafb;
        }
        .info-row { display: flex; align-items: baseline; gap: 6px; }
        .info-label { font-size: 11px; color: #6b7280; white-space: nowrap; }
        .info-value { font-weight: 600; color: #111827; }

        /* ── Amount box ── */
        .amount-box {
            text-align: center;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .amount-label { font-size: 12px; opacity: 0.85; margin-bottom: 6px; }
        .amount-value { font-size: 36px; font-weight: 800; }
        .amount-currency { font-size: 16px; opacity: 0.75; margin-right: 6px; }

        /* ── Method badge ── */
        .method-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
        }
        .method-cash          { background: #dcfce7; color: #166534; }
        .method-bank_transfer { background: #dbeafe; color: #1e40af; }
        .method-cheque        { background: #fef3c7; color: #92400e; }

        /* ── Cheque table ── */
        .cheque-section {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .cheque-title { font-weight: 700; color: #92400e; margin-bottom: 10px; font-size: 13px; }
        .cheque-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }

        /* ── Notes ── */
        .notes-section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #374151;
        }
        .notes-title { font-size: 11px; color: #6b7280; margin-bottom: 4px; }

        /* ── Footer ── */
        .footer {
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            margin-top: 20px;
        }

        /* ── Print button ── */
        .print-btn {
            display: block;
            margin: 0 auto 20px;
            padding: 10px 32px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            font-family: inherit;
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨 طباعة</button>

{{-- ── Header ── --}}
<div class="header">
    <div class="company-name">{{ \App\Models\SystemSetting::get('company.name', 'نور القدس') }}</div>
    <div class="company-sub">{{ $receipt->businessUnit->name ?? \App\Models\SystemSetting::get('company.name', 'نور القدس للأدوات الصحية') }}</div>
    <div class="doc-title">إيصال تحصيل</div>
</div>

{{-- ── Amount ── --}}
<div class="amount-box">
    <div class="amount-label">المبلغ المستلم</div>
    <div class="amount-value">
        {{ number_format((float) $receipt->amount, 2) }}
        <span class="amount-currency">ج.م</span>
    </div>
    <div>
        <span class="method-badge method-{{ $receipt->payment_method }}">
            {{ $receipt->payment_method_label }}
        </span>
    </div>
</div>

{{-- ── Info grid ── --}}
<div class="info-section">
    <div class="info-row">
        <span class="info-label">رقم الإيصال:</span>
        <span class="info-value">{{ $receipt->receipt_number }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">تاريخ الإيصال:</span>
        <span class="info-value">{{ $receipt->receipt_date?->format('Y-m-d') }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">العميل:</span>
        <span class="info-value">{{ $receipt->customer?->name }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">كود العميل:</span>
        <span class="info-value">{{ $receipt->customer?->code }}</span>
    </div>
    @if($receipt->invoice)
    <div class="info-row">
        <span class="info-label">الفاتورة:</span>
        <span class="info-value">{{ $receipt->invoice->reference_number }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">المتبقي بعد السداد:</span>
        <span class="info-value">{{ number_format($receipt->invoice->remaining_amount, 2) }} ج.م</span>
    </div>
    @endif
    @if($receipt->treasury)
    <div class="info-row">
        <span class="info-label">الخزينة:</span>
        <span class="info-value">{{ $receipt->treasury->name }}</span>
    </div>
    @endif
    @if($receipt->bank_reference)
    <div class="info-row">
        <span class="info-label">مرجع التحويل:</span>
        <span class="info-value">{{ $receipt->bank_reference }}</span>
    </div>
    @endif
    <div class="info-row">
        <span class="info-label">قيد محاسبي:</span>
        <span class="info-value">{{ $receipt->journalEntry?->entry_number ?? '—' }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">أنشأه:</span>
        <span class="info-value">{{ $receipt->createdBy?->name }}</span>
    </div>
</div>

{{-- ── Cheque details ── --}}
@if($receipt->payment_method === 'cheque' && $receipt->cheque_details)
<div class="cheque-section">
    <div class="cheque-title">📋 بيانات الشيك</div>
    <div class="cheque-grid">
        <div class="info-row">
            <span class="info-label">رقم الشيك:</span>
            <span class="info-value">{{ $receipt->cheque_details['cheque_number'] ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">البنك:</span>
            <span class="info-value">{{ $receipt->cheque_details['bank_name'] ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ الاستحقاق:</span>
            <span class="info-value">{{ $receipt->cheque_details['due_date'] ?? '—' }}</span>
        </div>
    </div>
</div>
@endif

{{-- ── Notes ── --}}
@if($receipt->notes)
<div class="notes-section">
    <div class="notes-title">ملاحظات:</div>
    {{ $receipt->notes }}
</div>
@endif

{{-- ── Footer ── --}}
<div class="footer">
    طُبع بواسطة نظام نور القدس — {{ now()->format('Y-m-d H:i') }}
</div>

</body>
</html>
