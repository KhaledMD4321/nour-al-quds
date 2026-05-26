<x-filament-widgets::widget>
    @php $units = $this->getUnits(); @endphp

    <div style="direction: rtl; font-family: Cairo, sans-serif;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <span style="font-size:18px;">🏬</span>
            <span style="font-weight:800; font-size:16px; color:#111827;">مقارنة الوحدات — هذا الشهر</span>
        </div>

        @if(count($units) > 0)
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
                @foreach($units as $u)
                    @php
                        $accent = $u['is_showroom'] ? '#7c3aed' : '#1e40af';
                        $tint   = $u['is_showroom'] ? '#f5f3ff' : '#eff6ff';
                        $marginColor = $u['gross_margin'] >= 20 ? '#059669' : ($u['gross_margin'] >= 10 ? '#d97706' : '#dc2626');
                    @endphp
                    <div style="background:white; border:1px solid #e5e7eb; border-top:4px solid {{ $accent }}; border-radius:12px; overflow:hidden;">
                        <div style="background:{{ $tint }}; padding:12px 16px; font-weight:800; font-size:15px; color:{{ $accent }};">
                            {{ $u['is_showroom'] ? '🏬' : '🏭' }} {{ $u['name'] }}
                        </div>
                        <div style="padding:6px 16px 14px;">
                            @foreach([
                                ['المبيعات (صافي)', number_format($u['sales'], 2).' ج.م', '#111827'],
                                ['مجمل الربح', number_format($u['gross_profit'], 2).' ج.م', '#059669'],
                                ['هامش الربح', number_format($u['gross_margin'], 1).'%', $marginColor],
                                ['صافي الربح', number_format($u['net_profit'], 2).' ج.م', $u['net_profit'] >= 0 ? '#059669' : '#dc2626'],
                                ['النقدية', number_format($u['cash'], 2).' ج.م', '#1e40af'],
                            ] as $row)
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6;">
                                    <span style="font-size:12px; color:#6b7280;">{{ $row[0] }}</span>
                                    <span style="font-size:14px; font-weight:700; color:{{ $row[2] }};">{{ $row[1] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="padding:30px; text-align:center; color:#9ca3af; font-size:14px; background:white; border:1px solid #e5e7eb; border-radius:12px;">
                لا توجد وحدات تشغيلية مفعّلة
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
