<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $headers = $this->getPreviewHeaders();
            $preview = $this->getPreviewData();
        @endphp

        {{-- ── معاينة أول 10 صفوف ── --}}
        @if($preview->isNotEmpty())
        <div style="margin-bottom: 16px;">
            <div style="font-size: 13px; color: #6b7280; margin-bottom: 8px; font-weight: 600;">
                معاينة أول {{ $preview->count() }} صف (سيتم تصدير الكل عند الضغط على "تصدير Excel")
            </div>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f9fafb;">
                        <tr>
                            @foreach($headers as $h)
                                <th style="padding: 9px 12px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">
                                    {{ $h }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview as $row)
                            <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                                @foreach((array)$row as $cell)
                                    <td style="padding: 7px 12px; font-size: 12px; white-space: nowrap;">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div style="text-align: center; padding: 40px; color: #9ca3af; font-size: 14px; background: white; border: 1px solid #e5e7eb; border-radius: 12px;">
            <div style="font-size: 36px; margin-bottom: 12px;">📊</div>
            اختر نوع البيانات واضغط "تصدير Excel" للتنزيل
        </div>
        @endif

        {{-- ── زر التصدير ── --}}
        <div style="margin-top: 16px;">
            {{ $this->exportAction }}
        </div>

    </div>
</x-filament-panels::page>
