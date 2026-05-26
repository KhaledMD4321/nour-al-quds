<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $data = $this->getData();
            $isTop = $this->report_type === 'top';
        @endphp

        @if($isTop)
            @php
                $grand = $data->sum('total');
                $vitalFew = $data->filter(fn ($r) => $r->cumulative_pct <= 80)->count();
            @endphp
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;">
                @foreach([
                    ['label' => 'عدد العملاء', 'value' => number_format($data->count()), 'color' => '#1e40af', 'bg' => '#eff6ff'],
                    ['label' => 'إجمالي المبيعات', 'value' => number_format($grand, 2).' ج.م', 'color' => '#166534', 'bg' => '#f0fdf4'],
                    ['label' => 'عملاء يصنعون 80% من المبيعات', 'value' => number_format(max(1, $vitalFew)), 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                ] as $card)
                    <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                        <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                        <div style="font-size: 17px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px;">
                @foreach([
                    ['label' => 'عملاء متوقفون', 'value' => number_format($data->count()), 'color' => '#b45309', 'bg' => '#fffbeb'],
                    ['label' => 'قيمة تاريخية معرّضة', 'value' => number_format($data->sum('lifetime'), 2).' ج.م', 'color' => '#b45309', 'bg' => '#fffbeb'],
                ] as $card)
                    <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                        <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                        <div style="font-size: 17px; font-weight: 700; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: {{ $isTop ? '#1e3a5f' : '#7c2d12' }}; color: white;">
                    <tr>
                        @if($isTop)
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">#</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">العميل</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الفواتير</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المبيعات</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الحصة</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">التراكمي</th>
                        @else
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">العميل</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">آخر شراء</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">منذ (يوم)</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">عدد الطلبات</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">القيمة التاريخية</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $i => $row)
                        @if($isTop)
                            <tr style="border-bottom: 1px solid #f3f4f6; background: {{ $row->cumulative_pct <= 80 ? '#f5f3ff' : 'transparent' }};">
                                <td style="padding: 8px 14px; font-size: 12px; color: #9ca3af;">{{ $i + 1 }}</td>
                                <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->customer_name }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #6b7280;">{{ $row->invoice_count }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #166534;">{{ number_format($row->total, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->share_pct, 1) }}%</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #7c3aed;">{{ number_format($row->cumulative_pct, 1) }}%</td>
                            </tr>
                        @else
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->customer_code }}</td>
                                <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->customer_name }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ $row->last_sale }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #dc2626;">{{ $row->days_since }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #6b7280;">{{ $row->orders }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #b45309;">{{ number_format($row->lifetime, 2) }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #9ca3af;">
                                {{ $isTop ? 'لا توجد مبيعات في الفترة' : '🎉 لا يوجد عملاء متوقفون' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($isTop)
            <div style="font-size: 11px; color: #9ca3af; margin-top: 10px;">
                الصفوف المظللة = العملاء الذين يصنعون أول 80% من المبيعات (ركّز عليهم).
            </div>
        @endif

    </div>
</x-filament-panels::page>
