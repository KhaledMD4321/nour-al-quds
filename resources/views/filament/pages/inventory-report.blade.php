<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $data = $this->getData();
            $type = $this->report_type;
        @endphp

        {{-- ══════════════════════ أرصدة المخزون ══════════════════════ --}}
        @if($type === 'balance')
            @php
                $totalValue = $data->sum('total_value');
                $belowMin   = $data->where('below_min', true)->count();
            @endphp

            @if($belowMin > 0)
            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; color: #991b1b; font-weight: 600; font-size: 13px;">
                ⚠️ {{ $belowMin }} صنف تحت الحد الأدنى
            </div>
            @endif

            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #1e40af; color: white;">
                        <tr>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الصنف</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">المخزن</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الكمية</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">متوسط التكلفة</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">القيمة الإجمالية</th>
                            <th style="padding: 10px 14px; text-align: center; font-size: 12px;">الحد الأدنى</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr style="border-bottom: 1px solid #f3f4f6; {{ $row->below_min ? 'background:#fef2f2;' : '' }}"
                                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='{{ $row->below_min ? '#fef2f2' : '' }}'">
                                <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->product_code }}</td>
                                <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->product_name }}</td>
                                <td style="padding: 8px 14px; font-size: 13px; color: #6b7280;">{{ $row->warehouse_name }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: {{ $row->below_min ? '#dc2626' : '#111827' }};">
                                    {{ number_format($row->quantity, 2) }}
                                    @if($row->below_min)
                                        <span style="font-size: 10px; color: #dc2626; margin-right: 4px;">⚠</span>
                                    @endif
                                </td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->avg_cost, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600;">{{ number_format($row->total_value, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: center; font-size: 12px; color: #9ca3af;">{{ number_format($row->min_stock_level, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="padding: 40px; text-align: center; color: #9ca3af;">لا توجد بيانات</td></tr>
                        @endforelse
                    </tbody>
                    @if($data->isNotEmpty())
                    <tfoot style="background: #111827; color: white;">
                        <tr>
                            <td colspan="5" style="padding: 12px 14px; font-weight: 700;">إجمالي قيمة المخزون</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700; font-size: 15px;">{{ number_format($totalValue, 2) }} ج.م</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

        {{-- ══════════════════════ الأصناف الراكدة ══════════════════════ --}}
        @elseif($type === 'slow')
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #92400e; color: white;">
                        <tr>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الصنف</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">المخزن</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الكمية</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">متوسط التكلفة</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#fffbeb'" onmouseout="this.style.background=''">
                                <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->product_code }}</td>
                                <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->product_name }}</td>
                                <td style="padding: 8px 14px; font-size: 13px; color: #6b7280;">{{ $row->warehouse_name }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->quantity, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->avg_cost, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #92400e;">{{ number_format($row->total_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="padding: 40px; text-align: center; color: #9ca3af;">لا توجد أصناف راكدة</td></tr>
                        @endforelse
                    </tbody>
                    @if($data->isNotEmpty())
                    <tfoot style="background: #111827; color: white;">
                        <tr>
                            <td colspan="5" style="padding: 12px 14px; font-weight: 700;">إجمالي قيمة الراكد</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700;">{{ number_format($data->sum('total_value'), 2) }} ج.م</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

        {{-- ══════════════════════ حركة صنف ══════════════════════ --}}
        @elseif($type === 'movement')
            @if(! $this->product_id)
                <div style="text-align: center; padding: 60px; color: #9ca3af; font-size: 15px;">
                    <div style="font-size: 36px; margin-bottom: 12px;">📦</div>
                    اختر صنف لعرض حركاته
                </div>
            @else
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f9fafb;">
                            <tr>
                                <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">التاريخ</th>
                                <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المخزن</th>
                                <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">النوع</th>
                                <th style="padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الكمية</th>
                                <th style="padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الرصيد بعد</th>
                                <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $mov)
                                @php
                                    $typeLabel = match($mov->type) {
                                        'in'         => ['label' => 'وارد',   'color' => '#059669', 'bg' => '#dcfce7'],
                                        'out'        => ['label' => 'صادر',   'color' => '#dc2626', 'bg' => '#fef2f2'],
                                        'adjustment' => ['label' => 'تسوية',  'color' => '#d97706', 'bg' => '#fef3c7'],
                                        default      => ['label' => $mov->type, 'color' => '#6b7280', 'bg' => '#f3f4f6'],
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                                    <td style="padding: 8px 14px; font-size: 13px;">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                    <td style="padding: 8px 14px; font-size: 13px; color: #6b7280;">{{ $mov->warehouse->name }}</td>
                                    <td style="padding: 8px 14px;">
                                        <span style="display:inline-block; padding:2px 10px; font-size:11px; border-radius:999px; background:{{ $typeLabel['bg'] }}; color:{{ $typeLabel['color'] }};">
                                            {{ $typeLabel['label'] }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: {{ $typeLabel['color'] }};">
                                        {{ $mov->type === 'out' ? '-' : '+' }}{{ number_format($mov->quantity, 2) }}
                                    </td>
                                    <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 700; color: #1e40af;">
                                        {{ number_format($mov->balance_after, 2) }}
                                    </td>
                                    <td style="padding: 8px 14px; font-size: 12px; color: #9ca3af;">{{ $mov->notes }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="padding: 40px; text-align: center; color: #9ca3af;">لا توجد حركات في الفترة المحددة</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

    </div>
</x-filament-panels::page>
