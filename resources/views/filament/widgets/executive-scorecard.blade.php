<x-filament-widgets::widget>
    @php
        $kpis = $this->getKpis();
        $palette = [
            'green' => ['bg' => '#ecfdf5', 'border' => '#6ee7b7', 'text' => '#047857', 'dot' => '#10b981'],
            'amber' => ['bg' => '#fffbeb', 'border' => '#fcd34d', 'text' => '#b45309', 'dot' => '#f59e0b'],
            'red'   => ['bg' => '#fef2f2', 'border' => '#fca5a5', 'text' => '#b91c1c', 'dot' => '#ef4444'],
            'gray'  => ['bg' => '#f9fafb', 'border' => '#e5e7eb', 'text' => '#6b7280', 'dot' => '#9ca3af'],
        ];
    @endphp

    <div style="direction: rtl; font-family: Cairo, sans-serif;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <span style="font-size:18px;">🧭</span>
            <span style="font-weight:800; font-size:16px; color:#111827;">النظرة التنفيذية — صحّة الشركة الآن</span>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px;">
            @foreach($kpis as $kpi)
                @php $c = $palette[$kpi['status']] ?? $palette['gray']; @endphp
                <div style="background:{{ $c['bg'] }}; border:1px solid {{ $c['border'] }}; border-radius:12px; padding:14px 16px; position:relative;">
                    <span style="position:absolute; top:14px; left:14px; width:10px; height:10px; border-radius:50%; background:{{ $c['dot'] }};"></span>

                    <div style="display:flex; align-items:center; gap:6px; color:#6b7280; font-size:12px; font-weight:600; margin-bottom:8px;">
                        <span style="font-size:15px;">{{ $kpi['icon'] }}</span>
                        <span>{{ $kpi['label'] }}</span>
                    </div>

                    <div style="font-size:22px; font-weight:800; color:{{ $c['text'] }}; line-height:1.2;">
                        {{ $kpi['display'] }}
                    </div>

                    @if(! empty($kpi['hint']))
                        <div style="font-size:11px; color:#9ca3af; margin-top:4px;">{{ $kpi['hint'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
