<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $dpo = $this->getDpo();
            $rows = $this->getScorecard();
        @endphp

        {{-- ── بطاقات ── --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
            @foreach([
                ['label' => 'المستحق للموردين', 'value' => number_format($dpo->ap, 2).' ج.م', 'color' => '#b45309', 'bg' => '#fffbeb'],
                ['label' => 'متوسط فترة السداد (DPO)', 'value' => $dpo->dpo.' يوم', 'color' => '#1e40af', 'bg' => '#eff6ff'],
                ['label' => 'صافي المشتريات', 'value' => number_format($dpo->purchases, 2).' ج.م', 'color' => '#166534', 'bg' => '#f0fdf4'],
                ['label' => 'إجمالي المرتجعات', 'value' => number_format($rows->sum('returns'), 2).' ج.م', 'color' => '#dc2626', 'bg' => '#fef2f2'],
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
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">المورد</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الفواتير</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المشتريات</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المسدّد</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المستحق</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المرتجعات</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">نسبة المرتجع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $rrColor = $row->return_rate >= 10 ? '#dc2626' : ($row->return_rate > 0 ? '#b45309' : '#9ca3af'); @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->supplier }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #6b7280;">{{ $row->invoices }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600;">{{ number_format($row->purchases, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #166534;">{{ number_format($row->paid, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: {{ $row->outstanding > 0 ? '#b45309' : '#9ca3af' }};">{{ number_format($row->outstanding, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #dc2626;">{{ number_format($row->returns, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 700; color: {{ $rrColor }};">{{ number_format($row->return_rate, 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: #9ca3af;">لا توجد مشتريات في الفترة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-filament-panels::page>
