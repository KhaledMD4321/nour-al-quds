<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>كشف حساب مورد — {{ $supplier->name }}</title>
    <style>
        @page { size: A4; margin: 12mm 14mm 14mm 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'xbriyaz', sans-serif; font-size: 12px; color: #1a1a1a; direction: rtl; background: #fff; }

        .header { text-align: center; border-bottom: 3px solid #92400e; padding-bottom: 12px; margin-bottom: 16px; }
        .company-name { font-size: 20px; font-weight: bold; color: #92400e; }
        .doc-title { font-size: 15px; font-weight: bold; color: #374151; margin-top: 6px; }
        .doc-period { font-size: 11px; color: #6b7280; margin-top: 3px; }

        .supplier-bar {
            background: #92400e; color: white; border-radius: 8px;
            padding: 12px 16px; margin-bottom: 14px;
            display: table; width: 100%;
        }
        .supplier-bar-right { display: table-cell; vertical-align: middle; }
        .supplier-bar-left  { display: table-cell; vertical-align: middle; text-align: left; }
        .supplier-name  { font-size: 15px; font-weight: bold; }
        .supplier-meta  { font-size: 10px; opacity: .8; margin-top: 2px; }
        .balance-label  { font-size: 10px; opacity: .8; }
        .balance-amount { font-size: 20px; font-weight: bold; }

        .summary { display: table; width: 100%; margin-bottom: 14px; }
        .card { display: table-cell; width: 25%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; text-align: center; }
        .card + .card { border-right: none; }
        .card-label { font-size: 10px; color: #6b7280; margin-bottom: 4px; }
        .card-value { font-size: 15px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead { background: #f3f4f6; }
        th { padding: 8px 10px; text-align: right; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        th.num { text-align: left; }
        td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; }
        td.num { text-align: left; }
        .opening-row { background: #f3f4f6; font-weight: 600; }
        tfoot { background: #111827; color: white; }
        tfoot td { padding: 10px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-name">{{ \App\Models\SystemSetting::get('company.name', 'نور القدس للأدوات الصحية') }}</div>
        <div class="doc-title">كشف حساب مورد</div>
        <div class="doc-period">
            الفترة:
            {{ $from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : 'بداية التأسيس' }}
            —
            {{ $to ? \Carbon\Carbon::parse($to)->format('d/m/Y') : 'اليوم' }}
            &nbsp;|&nbsp; تاريخ الطباعة: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="supplier-bar">
        <div class="supplier-bar-right">
            <div class="supplier-name">{{ $supplier->name }}</div>
            <div class="supplier-meta">{{ $supplier->code }} | {{ $supplier->phone ?? '—' }}</div>
        </div>
        <div class="supplier-bar-left">
            <div class="balance-label">الرصيد المستحق</div>
            <div class="balance-amount">
                {{ number_format(abs($closing), 2) }} ج.م
                <span style="font-size:11px; font-weight:400;">
                    {{ $closing > 0 ? '(مستحق له)' : ($closing < 0 ? '(زيادة دفع)' : '') }}
                </span>
            </div>
        </div>
    </div>

    <div class="summary">
        <div class="card">
            <div class="card-label">رصيد أول المدة</div>
            <div class="card-value" style="color:#92400e;">{{ number_format(abs($opening), 2) }}</div>
            <div style="font-size:9px; color:#9ca3af;">{{ $opening >= 0 ? 'مستحق له' : 'رصيد سالب' }}</div>
        </div>
        <div class="card">
            <div class="card-label">إجمالي فواتير</div>
            <div class="card-value" style="color:#dc2626;">{{ number_format($totalCredit, 2) }}</div>
        </div>
        <div class="card">
            <div class="card-label">إجمالي مدفوعات</div>
            <div class="card-value" style="color:#059669;">{{ number_format($totalDebit, 2) }}</div>
        </div>
        <div class="card" style="background:{{ $closing > 0 ? '#fffbeb' : '#f0fdf4' }}; border-color:{{ $closing > 0 ? '#fde68a' : '#86efac' }};">
            <div class="card-label">صافي المستحق</div>
            <div class="card-value" style="color:{{ $closing > 0 ? '#92400e' : '#059669' }};">{{ number_format(abs($closing), 2) }}</div>
            <div style="font-size:9px; color:#9ca3af;">{{ $closing > 0 ? 'مستحق له' : 'رصيد زيادة' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>التاريخ</th><th>المرجع</th><th>البيان</th>
                <th class="num">مدين (مدفوعات)</th><th class="num">دائن (فواتير)</th><th class="num">الرصيد</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening-row">
                <td style="color:#9ca3af;">—</td><td style="color:#9ca3af;">—</td>
                <td>رصيد أول المدة</td>
                <td class="num" style="color:#9ca3af;">—</td>
                <td class="num" style="color:#9ca3af;">—</td>
                <td class="num" style="color:#92400e; font-weight:700;">
                    {{ number_format(abs($opening), 2) }}
                    <span style="font-size:9px; color:#9ca3af;">{{ $opening >= 0 ? 'مستحق له' : 'دائن' }}</span>
                </td>
            </tr>
            @php $running = $opening; @endphp
            @forelse($lines as $line)
                @php $running += $line->credit - $line->debit; @endphp
                <tr>
                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($line->date)->format('d/m/Y') }}</td>
                    <td style="font-size:10px; color:#4b5563;">{{ $line->reference }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="num" style="color:{{ $line->debit > 0 ? '#059669' : '#d1d5db' }}; font-weight:{{ $line->debit > 0 ? '600' : '400' }};">
                        {{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}
                    </td>
                    <td class="num" style="color:{{ $line->credit > 0 ? '#dc2626' : '#d1d5db' }}; font-weight:{{ $line->credit > 0 ? '600' : '400' }};">
                        {{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}
                    </td>
                    <td class="num" style="color:{{ $running >= 0 ? '#92400e' : '#059669' }}; font-weight:700; white-space:nowrap;">
                        {{ number_format(abs($running), 2) }}
                        <span style="font-size:9px; color:#9ca3af; font-weight:400;">{{ $running >= 0 ? 'مستحق له' : 'رصيد دائن' }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:24px; text-align:center; color:#9ca3af;">لا توجد حركات في الفترة المحددة</td></tr>
            @endforelse
        </tbody>
        @if($lines->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">الإجمالي</td>
                <td class="num" style="color:#86efac;">{{ number_format($totalDebit, 2) }}</td>
                <td class="num" style="color:#fca5a5;">{{ number_format($totalCredit, 2) }}</td>
                <td class="num">
                    {{ number_format(abs($closing), 2) }}
                    <span style="font-size:10px; font-weight:400; color:#9ca3af;">{{ $closing >= 0 ? 'مستحق له' : 'رصيد دائن' }}</span>
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
