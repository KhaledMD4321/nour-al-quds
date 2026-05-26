<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $s = $this->getSummary();
            $b = $this->getBuckets();
            $trend = $this->getTrend();
            $dsoColor = $s->dso <= 30 ? '#166534' : ($s->dso <= 60 ? '#b45309' : '#dc2626');
            $rateColor = $s->collection_rate >= 90 ? '#166534' : ($s->collection_rate >= 70 ? '#b45309' : '#dc2626');
        @endphp

        {{-- ── بطاقات المؤشرات ── --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">
            @foreach([
                ['label' => 'إجمالي الذمم المدينة', 'value' => number_format($s->ar, 2).' ج.م', 'color' => '#1e40af', 'bg' => '#eff6ff'],
                ['label' => 'متوسط فترة التحصيل (DSO)', 'value' => $s->dso.' يوم', 'color' => $dsoColor, 'bg' => '#f9fafb'],
                ['label' => 'معدل التحصيل', 'value' => number_format($s->collection_rate, 1).'%', 'color' => $rateColor, 'bg' => '#f9fafb'],
                ['label' => 'المتأخر', 'value' => number_format($s->overdue, 2).' ج.م', 'color' => '#b91c1c', 'bg' => '#fef2f2'],
            ] as $card)
                <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                    <div style="font-size: 17px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- ── أعمار الديون (لقطة حالية) ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 18px; margin-bottom: 16px;">
            <div style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 10px;">أعمار الديون الآن</div>
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;">
                @foreach([
                    ['جاري', $b->current, '#166534'],
                    ['1–30 يوم', $b->days_30, '#65a30d'],
                    ['31–60 يوم', $b->days_60, '#b45309'],
                    ['61–90 يوم', $b->days_90, '#ea580c'],
                    ['+90 يوم', $b->over_90, '#dc2626'],
                ] as $bucket)
                    <div style="text-align: center; padding: 8px; border-radius: 8px; background: #f9fafb;">
                        <div style="font-size: 11px; color: #6b7280; margin-bottom: 4px;">{{ $bucket[0] }}</div>
                        <div style="font-size: 14px; font-weight: 700; color: {{ $bucket[2] }};">{{ number_format($bucket[1], 2) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── اتجاه: مبيعات مقابل تحصيلات ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <div style="padding: 12px 18px; border-bottom: 1px solid #e5e7eb; font-size: 13px; font-weight: 700; color: #111827;">
                المبيعات مقابل التحصيلات (آخر 6 أشهر)
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 9px 14px; text-align: right; font-size: 12px; color: #6b7280;">الشهر</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">المبيعات</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">التحصيلات</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">الفجوة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trend as $row)
                        @php $gapColor = $row->gap > 0 ? '#dc2626' : '#166534'; @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 9px 14px; font-size: 13px; font-weight: 600;">{{ $row->month }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color: #1e40af;">{{ number_format($row->sales, 2) }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color: #166534;">{{ number_format($row->collections, 2) }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; font-weight: 600; color: {{ $gapColor }};">{{ number_format($row->gap, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="font-size: 11px; color: #9ca3af; margin-top: 10px;">
            الفجوة موجبة = المبيعات أسرع من التحصيل (الذمم تكبر) · سالبة = التحصيل يفوق المبيعات.
        </div>

    </div>
</x-filament-panels::page>
