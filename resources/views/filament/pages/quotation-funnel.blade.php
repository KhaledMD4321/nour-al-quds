<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $s = $this->getSummary();
            $open = $this->getOpenQuotations();
            $winColor = $s->win_rate >= 50 ? '#166534' : ($s->win_rate >= 25 ? '#b45309' : '#dc2626');
            $pendingValue = round($s->total_value - $s->converted_value, 2);
        @endphp

        {{-- ── بطاقات ── --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
            @foreach([
                ['label' => 'عروض الأسعار', 'value' => number_format($s->total).' عرض', 'sub' => number_format($s->total_value, 2).' ج.م', 'color' => '#1e40af', 'bg' => '#eff6ff'],
                ['label' => 'محوّلة لفواتير', 'value' => number_format($s->converted).' عرض', 'sub' => number_format($s->converted_value, 2).' ج.م', 'color' => '#166534', 'bg' => '#f0fdf4'],
                ['label' => 'معدل التحويل', 'value' => number_format($s->win_rate, 1).'%', 'sub' => 'win rate', 'color' => $winColor, 'bg' => '#f9fafb'],
                ['label' => 'معلّقة', 'value' => number_format($s->pending).' عرض', 'sub' => number_format($pendingValue, 2).' ج.م', 'color' => '#b45309', 'bg' => '#fffbeb'],
            ] as $card)
                <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                    <div style="font-size: 17px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                    <div style="font-size: 10px; color: #9ca3af; margin-top: 2px;">{{ $card['sub'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- ── العروض المعلّقة للمتابعة ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <div style="padding: 12px 18px; border-bottom: 1px solid #e5e7eb; font-size: 13px; font-weight: 700; color: #111827;">
                عروض معلّقة للمتابعة
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 9px 14px; text-align: right; font-size: 12px; color: #6b7280;">المرجع</th>
                        <th style="padding: 9px 14px; text-align: right; font-size: 12px; color: #6b7280;">العميل</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">التاريخ</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">العمر (يوم)</th>
                        <th style="padding: 9px 14px; text-align: left; font-size: 12px; color: #6b7280;">القيمة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($open as $q)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 9px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $q->reference }}</td>
                            <td style="padding: 9px 14px; font-weight: 600; font-size: 13px;">{{ $q->customer }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px;">{{ $q->date }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color: {{ $q->age > 14 ? '#dc2626' : '#6b7280' }};">{{ $q->age }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #1e40af;">{{ number_format($q->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 40px; text-align: center; color: #9ca3af;">لا توجد عروض معلّقة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-filament-panels::page>
