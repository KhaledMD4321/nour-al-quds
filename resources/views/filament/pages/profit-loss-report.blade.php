<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $consolidated = $this->getConsolidated();

            if ($consolidated) {
                // عرض الوحدتين + الموحّد
                $units    = $consolidated->by_unit;
                $combined = $consolidated->consolidated;
            } else {
                $units    = [];
                $combined = $this->getReport();
            }

            function plRow(string $label, float $value, string $color = '#111827', bool $bold = false, bool $indent = false): void {
                $style = "padding: 10px 14px; font-size: 13px; color: {$color};" . ($bold ? 'font-weight:700;' : '') . ($indent ? 'padding-right:28px;' : '');
                echo "<tr style='border-bottom:1px solid #f3f4f6;'>";
                echo "<td style='{$style}'>{$label}</td>";
                echo "<td style='{$style} text-align:left;'>" . ($value != 0 ? number_format(abs($value), 2) : '—') . " ج.م</td>";
                echo "</tr>";
            }
        @endphp

        {{-- ── لو عرض مقارن بين الوحدتين ── --}}
        @if($consolidated && count($units) > 0)
            <div style="display: grid; grid-template-columns: repeat({{ count($units) }}, 1fr) 1fr; gap: 16px;">

                @foreach($units as $unitData)
                    @php $r = $unitData['report']; @endphp
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                        <div style="background: #1e40af; color: white; padding: 12px 16px; font-weight: 700; font-size: 14px;">
                            {{ $unitData['unit']->name }}
                        </div>
                        <table style="width: 100%; border-collapse: collapse;">
                            @include('filament.pages.partials.pl-rows', ['r' => $r])
                        </table>
                    </div>
                @endforeach

                {{-- الموحّد --}}
                <div style="background: white; border: 2px solid #1e40af; border-radius: 12px; overflow: hidden;">
                    <div style="background: #111827; color: white; padding: 12px 16px; font-weight: 700; font-size: 14px;">
                        الإجمالي الموحّد
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        @include('filament.pages.partials.pl-rows', ['r' => $combined])
                    </table>
                </div>

            </div>

        @else
            {{-- ── عرض وحدة واحدة ── --}}
            @php $r = $combined; @endphp
            <div style="max-width: 640px;">
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">

                        {{-- الإيرادات --}}
                        <tr style="background: #f0fdf4;">
                            <td colspan="2" style="padding: 10px 14px; font-weight: 700; font-size: 13px; color: #166534; letter-spacing:.5px;">الإيرادات</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding: 9px 14px 9px 28px; font-size: 13px;">إجمالي المبيعات</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px;">{{ number_format($r->gross_revenue, 2) }} ج.م</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding: 9px 14px 9px 28px; font-size: 13px; color:#dc2626;">( - ) الخصومات</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color:#dc2626;">({{ number_format($r->discounts, 2) }}) ج.م</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding: 9px 14px 9px 28px; font-size: 13px; color:#dc2626;">( - ) مرتجعات المبيعات</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color:#dc2626;">({{ number_format($r->sales_returns, 2) }}) ج.م</td>
                        </tr>
                        <tr style="background: #dcfce7; border-bottom: 2px solid #86efac;">
                            <td style="padding: 10px 14px; font-weight: 700; font-size: 14px; color:#166534;">صافي الإيرادات</td>
                            <td style="padding: 10px 14px; text-align: left; font-weight: 700; font-size: 14px; color:#166534;">{{ number_format($r->net_revenue, 2) }} ج.م</td>
                        </tr>

                        {{-- التكاليف --}}
                        <tr style="background: #fef2f2;">
                            <td colspan="2" style="padding: 10px 14px; font-weight: 700; font-size: 13px; color:#991b1b; letter-spacing:.5px;">تكلفة المبيعات</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding: 9px 14px 9px 28px; font-size: 13px; color:#dc2626;">( - ) تكلفة البضاعة المباعة</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color:#dc2626;">({{ number_format($r->cost_of_goods, 2) }}) ج.م</td>
                        </tr>
                        <tr style="background: #fef3c7; border-bottom: 2px solid #fde68a;">
                            <td style="padding: 10px 14px; font-weight: 700; font-size: 14px; color:#92400e;">مجمل الربح</td>
                            <td style="padding: 10px 14px; text-align: left; font-weight: 700; font-size: 14px; color: {{ $r->gross_profit >= 0 ? '#92400e' : '#dc2626' }};">
                                {{ number_format($r->gross_profit, 2) }} ج.م
                                <span style="font-size: 11px; font-weight: 400; color: #9ca3af;">({{ $r->gross_margin }}%)</span>
                            </td>
                        </tr>

                        {{-- المصروفات --}}
                        <tr style="background: #faf5ff;">
                            <td colspan="2" style="padding: 10px 14px; font-weight: 700; font-size: 13px; color:#6b21a8; letter-spacing:.5px;">المصروفات</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding: 9px 14px 9px 28px; font-size: 13px; color:#7c3aed;">( - ) إجمالي المصروفات</td>
                            <td style="padding: 9px 14px; text-align: left; font-size: 13px; color:#7c3aed;">({{ number_format($r->total_expenses, 2) }}) ج.م</td>
                        </tr>

                        {{-- صافي الربح --}}
                        <tr style="background: {{ $r->net_profit >= 0 ? '#1e40af' : '#991b1b' }};">
                            <td style="padding: 14px; font-weight: 700; font-size: 16px; color: white;">
                                {{ $r->net_profit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }}
                            </td>
                            <td style="padding: 14px; text-align: left; font-weight: 700; font-size: 16px; color: white;">
                                {{ number_format(abs($r->net_profit), 2) }} ج.م
                                <span style="font-size: 12px; font-weight: 400; opacity: .8;">({{ $r->net_margin }}%)</span>
                            </td>
                        </tr>

                    </table>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
