{{-- Partial: P&L rows for a single unit report object $r --}}
<tr style="background:#f0fdf4;">
    <td colspan="2" style="padding:8px 12px; font-weight:700; font-size:11px; color:#166534;">الإيرادات</td>
</tr>
<tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:7px 12px 7px 22px; font-size:12px;">إجمالي المبيعات</td>
    <td style="padding:7px 12px; text-align:left; font-size:12px;">{{ number_format($r->gross_revenue, 2) }}</td>
</tr>
<tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:7px 12px 7px 22px; font-size:12px; color:#dc2626;">(-) مرتجعات</td>
    <td style="padding:7px 12px; text-align:left; font-size:12px; color:#dc2626;">({{ number_format($r->sales_returns, 2) }})</td>
</tr>
<tr style="background:#dcfce7; border-bottom:2px solid #86efac;">
    <td style="padding:8px 12px; font-weight:700; font-size:13px; color:#166534;">صافي الإيرادات</td>
    <td style="padding:8px 12px; text-align:left; font-weight:700; font-size:13px; color:#166534;">{{ number_format($r->net_revenue, 2) }}</td>
</tr>

<tr style="background:#fef2f2;">
    <td colspan="2" style="padding:8px 12px; font-weight:700; font-size:11px; color:#991b1b;">التكاليف والمصروفات</td>
</tr>
<tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:7px 12px 7px 22px; font-size:12px; color:#dc2626;">(-) تكلفة البضاعة المباعة</td>
    <td style="padding:7px 12px; text-align:left; font-size:12px; color:#dc2626;">({{ number_format($r->cost_of_goods, 2) }})</td>
</tr>
<tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:7px 12px 7px 22px; font-size:12px; color:#7c3aed;">(-) المصروفات</td>
    <td style="padding:7px 12px; text-align:left; font-size:12px; color:#7c3aed;">({{ number_format($r->total_expenses, 2) }})</td>
</tr>

<tr style="background: {{ $r->net_profit >= 0 ? '#1e40af' : '#991b1b' }};">
    <td style="padding:10px 12px; font-weight:700; font-size:14px; color:white;">
        {{ $r->net_profit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }}
    </td>
    <td style="padding:10px 12px; text-align:left; font-weight:700; font-size:14px; color:white;">
        {{ number_format(abs($r->net_profit), 2) }}
        <span style="font-size:10px; font-weight:400; opacity:.8;">({{ $r->net_margin }}%)</span>
    </td>
</tr>
