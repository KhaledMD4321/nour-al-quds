<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $f = $this->getForecast();
            $endColor = $f['ending_balance'] < 0 ? '#dc2626' : '#166534';
            $shortfall = collect($f['buckets'])->contains(fn ($b) => ($b['running_balance'] ?? 0) < 0);
        @endphp

        @if($shortfall)
            <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; padding:12px 16px; margin-bottom:16px; color:#b91c1c; font-weight:600; font-size:13px;">
                ⚠️ تحذير: الرصيد التراكمي المتوقّع ينخفض تحت الصفر في إحدى الفترات — راجع التحصيل أو أجّل بعض المدفوعات.
            </div>
        @endif

        {{-- ── بطاقات الملخص ── --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
            @foreach([
                ['label' => 'النقدية الحالية', 'value' => number_format($f['opening_cash'], 2).' ج.م', 'color' => '#1e40af', 'bg' => '#eff6ff'],
                ['label' => 'متحصلات متوقعة', 'value' => number_format($f['total_inflow'], 2).' ج.م', 'color' => '#166534', 'bg' => '#f0fdf4'],
                ['label' => 'مدفوعات متوقعة', 'value' => number_format($f['total_outflow'], 2).' ج.م', 'color' => '#b45309', 'bg' => '#fffbeb'],
                ['label' => 'الرصيد المتوقّع نهاية المدة', 'value' => number_format($f['ending_balance'], 2).' ج.م', 'color' => $endColor, 'bg' => '#f9fafb'],
            ] as $card)
                <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                    <div style="font-size: 16px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #1e3a5f; color: white;">
                    <tr>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الفترة</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">داخل (متحصلات)</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">خارج (مدفوعات)</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الصافي</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الرصيد التراكمي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($f['buckets'] as $b)
                        @php
                            $netColor = $b['net'] >= 0 ? '#166534' : '#dc2626';
                            $runColor = $b['running_balance'] < 0 ? '#dc2626' : '#1e40af';
                            $runBg = $b['running_balance'] < 0 ? '#fef2f2' : 'transparent';
                        @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 9px 14px; font-size: 13px; font-weight: 600;">{{ $b['label'] }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color: #166534;">{{ number_format($b['inflow'], 2) }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color: #b45309;">{{ number_format($b['outflow'], 2) }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; font-weight: 600; color: {{ $netColor }};">{{ number_format($b['net'], 2) }}</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; font-weight: 700; color: {{ $runColor }}; background: {{ $runBg }};">{{ number_format($b['running_balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot style="background: #111827; color: white;">
                    <tr>
                        <td style="padding: 12px 14px; font-weight: 700;">الإجمالي</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #86efac;">{{ number_format($f['total_inflow'], 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #fcd34d;">{{ number_format($f['total_outflow'], 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700;">{{ number_format($f['total_inflow'] - $f['total_outflow'], 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: {{ $f['ending_balance'] < 0 ? '#fca5a5' : '#86efac' }};">{{ number_format($f['ending_balance'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</x-filament-panels::page>
