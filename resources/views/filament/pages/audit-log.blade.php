<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        @php
            $ops = $this->getRecentOperations();

            $typeColor = [
                'قيد يومي'   => '#6366f1',
                'سند قبض'    => '#059669',
                'سند صرف'    => '#dc2626',
                'شيك'        => '#d97706',
                'فاتورة بيع' => '#2563eb',
            ];
        @endphp

        {{-- Header card --}}
        <div style="background: #111827; color: white; border-radius: 10px; padding: 14px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 16px; font-weight: 700;">🛡️ سجل العمليات المالية</div>
                <div style="font-size: 11px; opacity: .6; margin-top: 3px;">آخر 100 عملية — مرتبة من الأحدث للأقدم</div>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: #6366f1;">{{ $ops->count() }}</div>
        </div>

        {{-- Operations table --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 10px 14px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">التاريخ والوقت</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">النوع</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المرجع</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المبلغ</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المستخدم</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">ملاحظة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ops as $op)
                        @php
                            $color = $typeColor[$op->type] ?? '#6b7280';
                        @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <td style="padding: 9px 14px; color: #374151; white-space: nowrap;">
                                {{ \Carbon\Carbon::parse($op->created_at)->format('d/m/Y') }}
                                <span style="font-size: 11px; color: #9ca3af; margin-right: 4px;">{{ \Carbon\Carbon::parse($op->created_at)->format('H:i') }}</span>
                            </td>
                            <td style="padding: 9px 14px;">
                                <span style="background: {{ $color }}1a; color: {{ $color }}; border: 1px solid {{ $color }}33; border-radius: 6px; padding: 2px 10px; font-size: 11px; font-weight: 600; white-space: nowrap;">
                                    {{ $op->type }}
                                </span>
                            </td>
                            <td style="padding: 9px 14px; font-family: monospace; font-size: 12px; color: #374151;">
                                {{ $op->reference ?? '—' }}
                            </td>
                            <td style="padding: 9px 14px; text-align: left; font-weight: 600; color: #111827;">
                                {{ $op->amount ? number_format($op->amount, 2) . ' ج.م' : '—' }}
                            </td>
                            <td style="padding: 9px 14px; color: #4b5563;">
                                {{ $op->user_name ?? '—' }}
                            </td>
                            <td style="padding: 9px 14px; font-size: 12px; color: #6b7280; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $op->notes ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #9ca3af;">
                                <div style="font-size: 36px; margin-bottom: 10px;">🛡️</div>
                                لا توجد عمليات مسجّلة بعد
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 10px; font-size: 11px; color: #9ca3af; text-align: left;">
            آخر تحديث: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</x-filament-panels::page>
