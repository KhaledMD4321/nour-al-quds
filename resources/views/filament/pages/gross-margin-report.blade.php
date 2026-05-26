<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $summary = $this->getSummary();
            $data = $this->getData();
            $byProduct = $this->report_type === 'product';
            $sumColor = $summary->margin_pct >= 20 ? '#059669' : ($summary->margin_pct >= 10 ? '#d97706' : '#dc2626');
        @endphp

        {{-- ── بطاقات الملخص ── --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
            @foreach([
                ['label' => 'إجمالي الإيراد', 'value' => number_format($summary->revenue, 2).' ج.م', 'color' => '#1e40af', 'bg' => '#eff6ff'],
                ['label' => 'تكلفة البضاعة المباعة', 'value' => number_format($summary->cogs, 2).' ج.م', 'color' => '#b45309', 'bg' => '#fffbeb'],
                ['label' => 'مجمل الربح', 'value' => number_format($summary->gross_profit, 2).' ج.م', 'color' => '#166534', 'bg' => '#f0fdf4'],
                ['label' => 'هامش الربح', 'value' => number_format($summary->margin_pct, 1).'%', 'color' => $sumColor, 'bg' => '#f9fafb'],
            ] as $card)
                <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                    <div style="font-size: 17px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #166534; color: white;">
                    <tr>
                        @if($byProduct)
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الصنف</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">المصنّع</th>
                        @else
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">المصنّع</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">عدد الأصناف</th>
                        @endif
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الكمية</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الإيراد</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">التكلفة</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">مجمل الربح</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الهامش</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                        @php $mc = $row->margin_pct >= 20 ? '#059669' : ($row->margin_pct >= 10 ? '#d97706' : '#dc2626'); @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            @if($byProduct)
                                <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->product_code }}</td>
                                <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->product_name }}</td>
                                <td style="padding: 8px 14px; font-size: 12px; color: #6b7280;">{{ $row->manufacturer }}</td>
                            @else
                                <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->manufacturer }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #6b7280;">{{ $row->products }}</td>
                            @endif
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->qty, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #1e40af;">{{ number_format($row->revenue, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #b45309;">{{ number_format($row->cogs, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 700; color: #166534;">{{ number_format($row->gross_profit, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 700; color: {{ $mc }};">{{ number_format($row->margin_pct, 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $byProduct ? 7 : 6 }}" style="padding: 40px; text-align: center; color: #9ca3af;">
                                لا توجد مبيعات في الفترة المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data->isNotEmpty())
                    <tfoot style="background: #111827; color: white;">
                        <tr>
                            <td colspan="{{ $byProduct ? 3 : 2 }}" style="padding: 12px 14px; font-weight: 700;">الإجمالي</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700;">{{ number_format($data->sum('qty'), 2) }}</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #93c5fd;">{{ number_format($data->sum('revenue'), 2) }}</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #fcd34d;">{{ number_format($data->sum('cogs'), 2) }}</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #86efac;">{{ number_format($data->sum('gross_profit'), 2) }}</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700;">{{ number_format($summary->margin_pct, 1) }}%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

    </div>
</x-filament-panels::page>
