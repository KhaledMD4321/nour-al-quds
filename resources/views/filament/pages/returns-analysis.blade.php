<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $s = $this->getSummary();
            $byProduct = $this->getByProduct();
            $trend = $this->getTrend();
            $rateColor = $s->rate <= 3 ? '#166534' : ($s->rate <= 8 ? '#b45309' : '#dc2626');
        @endphp

        {{-- ── بطاقات ── --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
            @foreach([
                ['label' => 'المبيعات', 'value' => number_format($s->sales, 2).' ج.م', 'color' => '#1e40af', 'bg' => '#eff6ff'],
                ['label' => 'المرتجعات', 'value' => number_format($s->returns, 2).' ج.م', 'color' => '#dc2626', 'bg' => '#fef2f2'],
                ['label' => 'نسبة المرتجع', 'value' => number_format($s->rate, 1).'%', 'color' => $rateColor, 'bg' => '#f9fafb'],
                ['label' => 'عدد المرتجعات', 'value' => number_format($s->count), 'color' => '#b45309', 'bg' => '#fffbeb'],
            ] as $card)
                <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                    <div style="font-size: 16px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- ── الاتجاه الشهري ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 16px;">
            <div style="padding: 12px 18px; border-bottom: 1px solid #e5e7eb; font-size: 13px; font-weight: 700; color: #111827;">نسبة المرتجع شهرياً (آخر 6 أشهر)</div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 9px 14px; text-align: right; font-size: 12px; color: #6b7280;">الشهر</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">المبيعات</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">المرتجعات</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">النسبة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trend as $row)
                        @php $rc = $row->rate <= 3 ? '#166534' : ($row->rate <= 8 ? '#b45309' : '#dc2626'); @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 9px 14px; font-size: 13px; font-weight: 600;">{{ $row->month }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color: #1e40af;">{{ number_format($row->sales, 2) }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color: #dc2626;">{{ number_format($row->returns, 2) }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; font-weight: 700; color: {{ $rc }};">{{ number_format($row->rate, 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── الأصناف الأكثر إرجاعاً ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <div style="padding: 12px 18px; border-bottom: 1px solid #e5e7eb; font-size: 13px; font-weight: 700; color: #111827;">الأصناف الأكثر إرجاعاً</div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #7f1d1d; color: white;">
                    <tr>
                        <th style="padding: 9px 14px; text-align: right; font-size: 12px;">الكود</th>
                        <th style="padding: 9px 14px; text-align: right; font-size: 12px;">الصنف</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px;">المبيعات</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px;">المرتجعات</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px;">نسبة الإرجاع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byProduct as $row)
                        @php $rc = $row->rate <= 3 ? '#166534' : ($row->rate <= 8 ? '#b45309' : '#dc2626'); @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->product_code }}</td>
                            <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->product_name }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->sales, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #dc2626;">{{ number_format($row->returns, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 700; color: {{ $rc }};">{{ number_format($row->rate, 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 40px; text-align: center; color: #9ca3af;">🎉 لا توجد مرتجعات في الفترة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-filament-panels::page>
