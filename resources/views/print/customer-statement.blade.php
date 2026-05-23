<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>كشف حساب عميل — {{ $customer->name }}</title>
    <style>
        @page { size: A4; margin: 12mm 14mm 14mm 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'xbriyaz', sans-serif; font-size: 12px; color: #1a1a1a; direction: rtl; background: #fff; }

        /* Header */
        .header { text-align: center; border-bottom: 3px solid #1e40af; padding-bottom: 12px; margin-bottom: 16px; }
        .company-name { font-size: 20px; font-weight: bold; color: #1e40af; }
        .doc-title { font-size: 15px; font-weight: bold; color: #374151; margin-top: 6px; }
        .doc-period { font-size: 11px; color: #6b7280; margin-top: 3px; }

        /* Customer info */
        .customer-bar {
            background: #1e40af; color: white; border-radius: 8px;
            padding: 12px 16px; margin-bottom: 14px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .customer-name { font-size: 15px; font-weight: bold; }
        .customer-meta { font-size: 10px; opacity: .8; margin-top: 2px; }
        .balance-label { font-size: 10px; opacity: .8; text-align: left; }
        .balance-amount { font-size: 20px; font-weight: bold; text-align: left; }

        /* Summary cards */
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; text-align: center; }
        .card-label { font-size: 10px; color: #6b7280; margin-bottom: 4px; }
        .card-value { font-size: 15px; font-weight: bold; }

        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead { background: #f3f4f6; }
        th { padding: 8px 10px; text-align: right; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        th.num { text-align: left; }
        td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; }
        td.num { text-align: left; }
        tr:hover { background: #f9fafb; }
        .opening-row { background: #f3f4f6; font-weight: 600; }
        tfoot { background: #111827; color: white; }
        tfoot td { padding: 10px; font-weight: bold; font-size: 12px; }

    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="company-name">{{ \App\Models\SystemSetting::get('company.name', 'نور القدس للأدوات الصحية') }}</div>
        <div class="doc-title">كشف حساب عميل</div>
        <div class="doc-period">
            الفترة:
            {{ $from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : 'بداية التأسيس' }}
            —
            {{ $to ? \Carbon\Carbon::parse($to)->format('d/m/Y') : 'اليوم' }}
            &nbsp;|&nbsp; تاريخ الطباعة: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Customer bar --}}
    <div class="customer-bar">
        <div>
            <div class="customer-name">{{ $customer->name }}</div>
            <div class="customer-meta">{{ $customer->code }} | {{ $customer->phone ?? '—' }}</div>
        </div>
        <div>
            <div class="balance-label">الرصيد الختامي</div>
            <div class="balance-amount">
                {{ number_format(abs($closing), 2) }} ج.م
                <span style="font-size:11px; font-weight:400;">
                    {{ $closing > 0 ? '(مدين)' : ($closing < 0 ? '(دائن)' : '') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="summary">
        <div class="card">
            <div class="card-label">رصيد أول المدة</div>
            <div class="card-value" style="color:#1e40af;">{{ number_format(abs($opening), 2) }}</div>
            <div style="font-size:9px; color:#9ca3af;">{{ $opening >= 0 ? 'مدين' : 'دائن' }}</div>
        </div>
        <div class="card">
            <div class="card-label">إجمالي مدين</div>
            <div class="card-value" style="color:#dc2626;">{{ number_format($totalDebit, 2) }}</div>
        </div>
        <div class="card">
            <div class="card-label">إجمالي دائن</div>
            <div class="card-value" style="color:#059669;">{{ number_format($totalCredit, 2) }}</div>
        </div>
        <div class="card" style="background:{{ $closing > 0 ? '#fef2f2' : '#f0fdf4' }}; border-color:{{ $closing > 0 ? '#fca5a5' : '#86efac' }};">
            <div class="card-label">الرصيد الختامي</div>
            <div class="card-value" style="color:{{ $closing > 0 ? '#dc2626' : '#059669' }};">{{ number_format(abs($closing), 2) }}</div>
            <div style="font-size:9px; color:#9ca3af;">{{ $closing >= 0 ? 'مدين' : 'دائن' }}</div>
        </div>
    </div>

    {{-- Transactions table --}}
    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>المرجع</th>
                <th>البيان</th>
                <th class="num">مدين</th>
                <th class="num">دائن</th>
                <th class="num">الرصيد</th>
            </tr>
        </thead>
        <tbody>
            {{-- Opening balance row --}}
            <tr class="opening-row">
                <td style="color:#9ca3af;">—</td>
                <td style="color:#9ca3af;">—</td>
                <td>رصيد أول المدة</td>
                <td class="num" style="color:#9ca3af;">—</td>
                <td class="num" style="color:#9ca3af;">—</td>
                <td class="num" style="color:#1e40af; font-weight:700;">
                    {{ number_format(abs($opening), 2) }}
                    <span style="font-size:9px; color:#9ca3af;">{{ $opening >= 0 ? 'مدين' : 'دائن' }}</span>
                </td>
            </tr>

            @php $running = $opening; @endphp
            @forelse($lines as $line)
                @php $running += $line->debit - $line->credit; @endphp
                <tr>
                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($line->date)->format('d/m/Y') }}</td>
                    <td style="font-size:10px; color:#4b5563;">{{ $line->reference }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="num" style="color:{{ $line->debit > 0 ? '#dc2626' : '#d1d5db' }}; font-weight:{{ $line->debit > 0 ? '600' : '400' }};">
                        {{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}
                    </td>
                    <td class="num" style="color:{{ $line->credit > 0 ? '#059669' : '#d1d5db' }}; font-weight:{{ $line->credit > 0 ? '600' : '400' }};">
                        {{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}
                    </td>
                    <td class="num" style="color:{{ $running >= 0 ? '#1e40af' : '#059669' }}; font-weight:700; white-space:nowrap;">
                        {{ number_format(abs($running), 2) }}
                        <span style="font-size:9px; color:#9ca3af; font-weight:400;">{{ $running >= 0 ? 'مدين' : 'دائن' }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding:24px; text-align:center; color:#9ca3af;">
                        لا توجد حركات في الفترة المحددة
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($lines->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">الإجمالي</td>
                <td class="num" style="color:#fca5a5;">{{ number_format($totalDebit, 2) }}</td>
                <td class="num" style="color:#86efac;">{{ number_format($totalCredit, 2) }}</td>
                <td class="num">
                    {{ number_format(abs($closing), 2) }}
                    <span style="font-size:10px; font-weight:400; color:#9ca3af;">{{ $closing >= 0 ? 'مدين' : 'دائن' }}</span>
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
