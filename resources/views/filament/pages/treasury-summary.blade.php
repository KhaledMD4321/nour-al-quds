<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ═══════════════════════════════════════════════════════
             بطاقة الإجمالي الكلي
             ═══════════════════════════════════════════════════════ --}}
        <div style="background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 10px 25px rgba(30,64,175,0.2);">
            <div style="font-size: 14px; opacity: 0.85; margin-bottom: 8px;">إجمالي رصيد جميع الخزائن</div>
            <div style="font-size: 38px; font-weight: 800; letter-spacing: -0.5px;">
                {{ number_format($this->getGrandTotal(), 2) }}
                <span style="font-size: 18px; opacity: 0.8; font-weight: 500;">ج.م</span>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             إجمالي كل وحدة
             ═══════════════════════════════════════════════════════ --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px;">
            @foreach($this->getTotalsByUnit() as $unitName => $total)
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">إجمالي {{ $unitName }}</div>
                    <div style="font-size: 28px; font-weight: 700; color: {{ $total >= 0 ? '#111827' : '#dc2626' }};">
                        {{ number_format($total, 2) }}
                        <span style="font-size: 14px; color: #9ca3af; font-weight: 500;">ج.م</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ═══════════════════════════════════════════════════════
             جدول تفاصيل الخزائن
             ═══════════════════════════════════════════════════════ --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 16px;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-size: 16px; font-weight: 700; color: #111827;">
                تفاصيل الخزائن
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الخزينة</th>
                        <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الوحدة</th>
                        <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">النوع</th>
                        <th style="padding: 12px 16px; text-align: end; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الرصيد الحالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->getTreasuries() as $treasury)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 14px 16px; font-weight: 600; color: #111827;">
                                {{ $treasury->name }}
                            </td>
                            <td style="padding: 14px 16px; color: #4b5563;">
                                {{ $treasury->businessUnit->name }}
                            </td>
                            <td style="padding: 14px 16px;">
                                <span style="display: inline-block; padding: 3px 10px; font-size: 11px; font-weight: 600; border-radius: 999px;
                                    {{ $treasury->type === 'cash'
                                        ? 'background: #dcfce7; color: #166534;'
                                        : 'background: #dbeafe; color: #1e40af;' }}">
                                    {{ $treasury->type === 'cash' ? 'نقدية' : 'بنك' }}
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: end; font-weight: 700; font-size: 15px;
                                color: {{ (float)$treasury->current_balance >= 0 ? '#059669' : '#dc2626' }};">
                                {{ number_format((float)$treasury->current_balance, 2) }}
                                <span style="font-size: 12px; color: #9ca3af; font-weight: 400;">ج.م</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 24px; text-align: center; color: #9ca3af;">لا توجد خزائن نشطة</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot style="background: #f9fafb; border-top: 2px solid #e5e7eb;">
                    <tr>
                        <td colspan="3" style="padding: 14px 16px; font-weight: 700; color: #374151;">الإجمالي الكلي</td>
                        <td style="padding: 14px 16px; text-align: end; font-weight: 800; font-size: 16px; color: #1e40af;">
                            {{ number_format($this->getGrandTotal(), 2) }}
                            <span style="font-size: 13px; color: #9ca3af; font-weight: 400;">ج.م</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- تنبيه الفصل المالي --}}
        <div style="padding: 12px 16px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; font-size: 13px; color: #92400e;">
            ⚠️ هذه الصفحة للنظرة الشمولية الإدارية فقط. الفصل المالي بين الوحدتين قائم في جميع المعاملات اليومية والتقارير المحاسبية.
        </div>

    </div>
</x-filament-panels::page>
