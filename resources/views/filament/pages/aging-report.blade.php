<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $data = $this->getData();
            $isCustomers = $this->report_type === 'customers';
            $nameKey = $isCustomers ? 'customer_name' : 'supplier_name';
            $codeKey = $isCustomers ? 'customer_code' : 'supplier_code';

            $totals = [
                'current' => $data->sum('current'),
                'days_30' => $data->sum('days_30'),
                'days_60' => $data->sum('days_60'),
                'days_90' => $data->sum('days_90'),
                'over_90' => $data->sum('over_90'),
                'total'   => $data->sum('total'),
            ];
        @endphp

        {{-- ── ملخص سريع ── --}}
        @if($data->isNotEmpty())
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px;">
            @foreach([
                ['label' => 'جاري (لم يُستحق)', 'value' => $totals['current'],  'color' => '#059669', 'bg' => '#dcfce7'],
                ['label' => '1 – 30 يوم',        'value' => $totals['days_30'],  'color' => '#d97706', 'bg' => '#fef3c7'],
                ['label' => '31 – 60 يوم',       'value' => $totals['days_60'],  'color' => '#ea580c', 'bg' => '#ffedd5'],
                ['label' => '61 – 90 يوم',       'value' => $totals['days_90'],  'color' => '#dc2626', 'bg' => '#fef2f2'],
                ['label' => 'أكثر من 90 يوم',   'value' => $totals['over_90'],  'color' => '#7c3aed', 'bg' => '#f5f3ff'],
            ] as $card)
            <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 6px;">{{ $card['label'] }}</div>
                <div style="font-size: 16px; font-weight: 700; color: {{ $card['color'] }};">{{ number_format($card['value'], 2) }}</div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #1e40af; color: white;">
                    <tr>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">{{ $isCustomers ? 'العميل' : 'المورد' }}</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 11px; background: #166534;">جاري</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 11px; background: #92400e;">1-30 يوم</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 11px; background: #c2410c;">31-60 يوم</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 11px; background: #991b1b;">61-90 يوم</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 11px; background: #5b21b6;">+90 يوم</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 700;">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->{$codeKey} }}</td>
                            <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->{$nameKey} }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #059669;">
                                {{ $row->current > 0 ? number_format($row->current, 2) : '—' }}
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #d97706;">
                                {{ $row->days_30 > 0 ? number_format($row->days_30, 2) : '—' }}
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #ea580c;">
                                {{ $row->days_60 > 0 ? number_format($row->days_60, 2) : '—' }}
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #dc2626;">
                                {{ $row->days_90 > 0 ? number_format($row->days_90, 2) : '—' }}
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #7c3aed; font-weight: 600;">
                                {{ $row->over_90 > 0 ? number_format($row->over_90, 2) : '—' }}
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-weight: 700; font-size: 13px;">
                                {{ number_format($row->total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 40px; text-align: center; color: #9ca3af; font-size: 14px;">
                                لا توجد ديون مستحقة في التاريخ المحدد
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data->isNotEmpty())
                <tfoot style="background: #111827; color: white;">
                    <tr>
                        <td colspan="2" style="padding: 12px 14px; font-weight: 700;">الإجمالي</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #86efac;">{{ number_format($totals['current'], 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #fde68a;">{{ number_format($totals['days_30'], 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #fed7aa;">{{ number_format($totals['days_60'], 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #fca5a5;">{{ number_format($totals['days_90'], 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #c4b5fd;">{{ number_format($totals['over_90'], 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; font-size: 15px;">{{ number_format($totals['total'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

    </div>
</x-filament-panels::page>
