<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $summary     = $this->getSummary();
            $data        = $this->getData();
            $isByProduct = $this->report_type === 'product';
        @endphp

        {{-- ── بطاقات الملخص ── --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
            @foreach([
                ['label' => 'عدد الفواتير',      'value' => number_format($summary->count),             'unit' => '',     'color' => '#1e40af', 'bg' => '#eff6ff'],
                ['label' => 'إجمالي المشتريات',  'value' => number_format($summary->total_amount, 2),  'unit' => 'ج.م', 'color' => '#92400e', 'bg' => '#fffbeb'],
                ['label' => 'المسدّد',            'value' => number_format($summary->paid_amount, 2),   'unit' => 'ج.م', 'color' => '#059669', 'bg' => '#dcfce7'],
                ['label' => 'المستحق',            'value' => number_format($summary->outstanding, 2),  'unit' => 'ج.م', 'color' => '#dc2626', 'bg' => '#fef2f2'],
            ] as $card)
            <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                <div style="font-size: 17px; font-weight: 700; color: {{ $card['color'] }};">
                    {{ $card['value'] }} <span style="font-size: 11px; font-weight: 400;">{{ $card['unit'] }}</span>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #92400e; color: white;">
                    <tr>
                        @if($isByProduct)
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الصنف</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الكمية المشتراة</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">التكلفة الإجمالية</th>
                        @else
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px;">المورد</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">عدد الفواتير</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الإجمالي</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المسدّد</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المستحق</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#fffbeb'" onmouseout="this.style.background=''">
                            @if($isByProduct)
                                <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #6b7280;">{{ $row->product_code }}</td>
                                <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->product_name }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px;">{{ number_format($row->total_qty, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #92400e;">{{ number_format($row->total_cost, 2) }}</td>
                            @else
                                <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">{{ $row->supplier_name }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #6b7280;">{{ $row->invoice_count }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; font-weight: 600;">{{ number_format($row->total, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #059669;">{{ number_format($row->paid, 2) }}</td>
                                <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: {{ $row->outstanding > 0 ? '#dc2626' : '#9ca3af' }}; font-weight: {{ $row->outstanding > 0 ? '600' : '400' }};">
                                    {{ $row->outstanding > 0 ? number_format($row->outstanding, 2) : '—' }}
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isByProduct ? 4 : 5 }}" style="padding: 40px; text-align: center; color: #9ca3af;">
                                لا توجد مشتريات في الفترة المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data->isNotEmpty())
                <tfoot style="background: #111827; color: white;">
                    <tr>
                        @if($isByProduct)
                            <td colspan="2" style="padding: 12px 14px; font-weight: 700;">الإجمالي</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700;">{{ number_format($data->sum('total_qty'), 2) }}</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #fde68a;">{{ number_format($data->sum('total_cost'), 2) }} ج.م</td>
                        @else
                            <td style="padding: 12px 14px; font-weight: 700;">الإجمالي</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700;">{{ $data->sum('invoice_count') }}</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #fde68a;">{{ number_format($data->sum('total'), 2) }} ج.م</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #6ee7b7;">{{ number_format($data->sum('paid'), 2) }} ج.م</td>
                            <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #fca5a5;">{{ number_format($data->sum('outstanding'), 2) }} ج.م</td>
                        @endif
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

    </div>
</x-filament-panels::page>
