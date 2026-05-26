<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── الفلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
            <div>
                <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:4px;">السنة</label>
                <select wire:model.live="year" style="padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-family:Cairo,sans-serif;">
                    @foreach($this->getYearOptions() as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()?->isSuperAdmin())
                <div>
                    <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:4px;">الوحدة</label>
                    <select wire:model.live="business_unit_id" style="padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-family:Cairo,sans-serif;">
                        @foreach($this->getUnitOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button wire:click="saveTargets" style="background:#1e40af; color:white; padding:9px 22px; border:none; border-radius:8px; font-family:Cairo,sans-serif; font-weight:700; cursor:pointer;">
                💾 حفظ الأهداف
            </button>
        </div>

        @php
            $rows = $this->getRows();
            $totalTarget = collect($rows)->sum('target');
            $totalActual = collect($rows)->sum('actual');
            $totalPct = $totalTarget > 0 ? round($totalActual / $totalTarget * 100, 1) : null;
        @endphp

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #1e3a5f; color: white;">
                    <tr>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الشهر</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">المستهدف</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الفعلي</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">الفرق</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">التحقيق</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $varColor = $row->variance >= 0 ? '#166534' : '#dc2626';
                            $pctColor = $row->achievement === null ? '#9ca3af' : ($row->achievement >= 100 ? '#166534' : ($row->achievement >= 80 ? '#b45309' : '#dc2626'));
                            $monthName = \Carbon\Carbon::create($year, $row->month, 1)->translatedFormat('F');
                        @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 6px 14px; font-size: 13px; font-weight: 600;">{{ $monthName }}</td>
                            <td style="padding: 6px 14px; text-align: left;">
                                <input type="number" step="0.01" min="0" wire:model="targets.{{ $row->month }}"
                                       style="width: 130px; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; text-align: left; font-family: Cairo, sans-serif; font-size: 13px;">
                            </td>
                            <td style="padding: 6px 14px; text-align: left; font-size: 13px; font-weight: 600; color: #1e40af;">{{ number_format($row->actual, 2) }}</td>
                            <td style="padding: 6px 14px; text-align: left; font-size: 13px; font-weight: 600; color: {{ $varColor }};">{{ number_format($row->variance, 2) }}</td>
                            <td style="padding: 6px 14px; text-align: left; font-size: 13px; font-weight: 700; color: {{ $pctColor }};">
                                {{ $row->achievement === null ? '—' : number_format($row->achievement, 1).'%' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot style="background: #111827; color: white;">
                    <tr>
                        <td style="padding: 12px 14px; font-weight: 700;">الإجمالي</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700;">{{ number_format($totalTarget, 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #93c5fd;">{{ number_format($totalActual, 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: {{ ($totalActual - $totalTarget) >= 0 ? '#86efac' : '#fca5a5' }};">{{ number_format($totalActual - $totalTarget, 2) }}</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700;">{{ $totalPct === null ? '—' : number_format($totalPct, 1).'%' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="font-size: 11px; color: #9ca3af; margin-top: 10px;">
            أدخل المستهدف الشهري ثم اضغط «حفظ الأهداف». التحقيق = الفعلي ÷ المستهدف.
        </div>

    </div>
</x-filament-panels::page>
