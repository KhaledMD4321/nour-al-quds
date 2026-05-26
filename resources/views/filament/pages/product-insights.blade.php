<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $data = $this->getData();
            $isAbc = $this->report_type === 'abc';
            $classColors = ['A' => '#166534', 'B' => '#b45309', 'C' => '#6b7280'];
        @endphp

        {{-- ── ملخص ── --}}
        @if($isAbc)
            @php $byClass = $data->groupBy('class'); @endphp
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;">
                @foreach(['A' => 'الأهم (80% من الإيراد)', 'B' => 'متوسطة (80–95%)', 'C' => 'الأقل (آخر 5%)'] as $cls => $label)
                    <div style="background: #f9fafb; border-radius: 10px; padding: 14px; text-align: center; border-top: 3px solid {{ $classColors[$cls] }};">
                        <div style="font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">فئة {{ $cls }} — {{ $label }}</div>
                        <div style="font-size: 17px; font-weight: 700; color: {{ $classColors[$cls] }};">{{ number_format(($byClass[$cls] ?? collect())->count()) }} صنف</div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 14px; margin-bottom: 20px; text-align: center;">
                <span style="font-size: 13px; color: #b45309; font-weight: 700;">🛒 {{ number_format($data->count()) }} صنف يحتاج إعادة طلب (وصل أو تحت الحد الأدنى)</span>
            </div>
        @endif

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: {{ $isAbc ? '#1e3a5f' : '#7c2d12' }}; color: white;">
                    <tr>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الصنف</th>
                        @if($isAbc)
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الكمية</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الإيراد</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">التراكمي</th>
                            <th style="padding: 10px 14px; text-align: center; font-size: 12px;">الفئة</th>
                        @else
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">المصنّع</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الرصيد</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الحد الأدنى</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">سرعة/يوم</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">أيام التغطية</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المقترح طلبه</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->product_code }}</td>
                            <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->product_name }}</td>
                            @if($isAbc)
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->qty, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #166534;">{{ number_format($row->revenue, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #6b7280;">{{ number_format($row->cumulative_pct, 1) }}%</td>
                                <td style="padding: 8px 14px; text-align: center;">
                                    <span style="background: {{ $classColors[$row->class] }}; color: white; padding: 2px 10px; border-radius: 10px; font-size: 12px; font-weight: 700;">{{ $row->class }}</span>
                                </td>
                            @else
                                <td style="padding: 8px 14px; font-size: 12px; color: #6b7280;">{{ $row->manufacturer }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #dc2626;">{{ number_format($row->current_stock, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #6b7280;">{{ number_format($row->min_level, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->velocity, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: {{ ($row->days_cover ?? 99) <= 7 ? '#dc2626' : '#6b7280' }};">{{ $row->days_cover === null ? '—' : $row->days_cover.' يوم' }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 700; color: #166534;">{{ number_format($row->suggested_order) }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #9ca3af;">
                                {{ $isAbc ? 'لا توجد مبيعات في الفترة' : '🎉 كل الأصناف فوق الحد الأدنى' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-filament-panels::page>
