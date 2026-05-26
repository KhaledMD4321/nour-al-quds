<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $data = $this->getData();
            $summary = $this->getSummary();
            $isTurnover = $this->report_type === 'turnover';
        @endphp

        {{-- ── بطاقات الملخص ── --}}
        <div style="display: grid; grid-template-columns: repeat({{ $isTurnover ? 3 : 2 }}, 1fr); gap: 12px; margin-bottom: 20px;">
            @if($isTurnover)
                @foreach([
                    ['label' => 'قيمة المخزون الحالي', 'value' => number_format($summary->value, 2).' ج.م', 'color' => '#1e40af', 'bg' => '#eff6ff'],
                    ['label' => 'تكلفة المبيعات (الفترة)', 'value' => number_format($summary->cogs, 2).' ج.م', 'color' => '#b45309', 'bg' => '#fffbeb'],
                    ['label' => 'معدل الدوران (سنوي)', 'value' => number_format($summary->turnover, 2).'×', 'color' => $summary->turnover >= 4 ? '#166534' : ($summary->turnover >= 2 ? '#b45309' : '#dc2626'), 'bg' => '#f9fafb'],
                ] as $card)
                    <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                        <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                        <div style="font-size: 17px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            @else
                @foreach([
                    ['label' => 'عدد الأصناف الراكدة', 'value' => number_format($summary->count), 'color' => '#b91c1c', 'bg' => '#fef2f2'],
                    ['label' => 'القيمة المجمّدة', 'value' => number_format($summary->value, 2).' ج.م', 'color' => '#b91c1c', 'bg' => '#fef2f2'],
                ] as $card)
                    <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                        <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                        <div style="font-size: 17px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: {{ $isTurnover ? '#1e3a5f' : '#7f1d1d' }}; color: white;">
                    <tr>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الصنف</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">المصنّع</th>
                        @if($isTurnover)
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الكمية المباعة</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">قيمة المخزون</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الدوران</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">أيام التغطية</th>
                        @else
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المخزن</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الكمية</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">القيمة المجمّدة</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">آخر حركة</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->product_code }}</td>
                            <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->product_name }}</td>
                            <td style="padding: 8px 14px; font-size: 12px; color: #6b7280;">{{ $row->manufacturer }}</td>
                            @if($isTurnover)
                                @php $tc = $row->turnover >= 4 ? '#059669' : ($row->turnover >= 2 ? '#d97706' : ($row->turnover > 0 ? '#dc2626' : '#9ca3af')); @endphp
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->qty_sold, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #1e40af;">{{ number_format($row->stock_value, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 700; color: {{ $tc }};">{{ number_format($row->turnover, 2) }}×</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #6b7280;">{{ $row->days_of_inventory === null ? '∞' : $row->days_of_inventory.' يوم' }}</td>
                            @else
                                <td style="padding: 8px 14px; text-align: left; font-size: 12px; color: #6b7280;">{{ $row->warehouse_name }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->quantity, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 700; color: #b91c1c;">{{ number_format($row->total_value, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 12px; color: #9ca3af;">{{ $row->last_movement ?? 'لا يوجد' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: #9ca3af;">
                                {{ $isTurnover ? 'لا يوجد مخزون في النطاق المحدد' : '🎉 لا توجد بضاعة راكدة' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-filament-panels::page>
