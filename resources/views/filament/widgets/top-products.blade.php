<x-filament-widgets::widget>
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; font-family: Cairo, sans-serif;">
        <div style="padding: 14px 18px; border-bottom: 1px solid #e5e7eb; font-weight: 700; font-size: 14px; color: #111827; direction: rtl;">
            🏆 أكثر 10 أصناف مبيعاً هذا الشهر
        </div>
        @php $products = $this->getProducts(); @endphp
        @if(count($products) > 0)
            <table style="width: 100%; border-collapse: collapse; direction: rtl;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 8px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">#</th>
                        <th style="padding: 8px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الصنف</th>
                        <th style="padding: 8px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الكمية</th>
                        <th style="padding: 8px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">القيمة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $i => $p)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 8px 14px; font-size: 12px; color: #9ca3af;">{{ $i + 1 }}</td>
                            <td style="padding: 8px 14px; font-size: 13px; font-weight: 600; color: #111827;">{{ $p->product_name }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px; color: #2563eb; font-weight: 600;">{{ number_format($p->total_qty, 2) }}</td>
                            <td style="padding: 8px 14px; text-align: left; font-weight: 700; color: #059669;">{{ number_format($p->total_value, 2) }} ج.م</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="padding: 40px; text-align: center; color: #9ca3af; font-size: 14px;">
                <div style="font-size: 32px; margin-bottom: 8px;">🛍️</div>
                لا توجد مبيعات هذا الشهر
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
