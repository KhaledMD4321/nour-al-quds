<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}

            {{-- رابط تحميل القالب --}}
            <div style="margin-top: 12px;">
                <a href="{{ \App\Filament\Pages\ImportCenterPage::getUrl() }}"
                   wire:click.prevent="getTemplate"
                   style="font-size: 12px; color: #1e40af; text-decoration: none; font-weight: 600;">
                    ⬇ تحميل قالب Excel لـ {{ match($this->import_type) { 'customers' => 'العملاء', 'suppliers' => 'الموردين', 'products' => 'الأصناف', default => $this->import_type } }}
                </a>
            </div>
        </div>

        {{-- ── نتيجة بعد الاستيراد ── --}}
        @if($this->imported)
            <div style="background: #dcfce7; border: 1px solid #86efac; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
                <div style="font-weight: 700; color: #166534; font-size: 15px; margin-bottom: 6px;">✓ تم الاستيراد بنجاح</div>
                <div style="font-size: 13px; color: #166534;">
                    تم إضافة {{ $this->imported_count }} سجل جديد، وتحديث {{ $this->updated_count }} سجل موجود
                </div>
            </div>
        @endif

        {{-- ── نتائج التحقق ── --}}
        @if($this->validated)

            {{-- الصفوف الخاطئة --}}
            @if(!empty($this->invalid_rows))
                <div style="background: white; border: 1px solid #fca5a5; border-radius: 12px; overflow: hidden; margin-bottom: 16px;">
                    <div style="background: #fef2f2; padding: 12px 16px; font-weight: 700; color: #991b1b; font-size: 13px; border-bottom: 1px solid #fca5a5;">
                        ⚠ صفوف برسائل تحقق ({{ count($this->invalid_rows) }})
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f9fafb;">
                            <tr>
                                <th style="padding: 8px 12px; text-align: right; font-size: 11px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">رقم الصف</th>
                                <th style="padding: 8px 12px; text-align: right; font-size: 11px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">البيانات</th>
                                <th style="padding: 8px 12px; text-align: right; font-size: 11px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الأخطاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->invalid_rows as $item)
                                <tr style="border-bottom: 1px solid #fef2f2; background: #fff5f5;">
                                    <td style="padding: 8px 12px; font-size: 12px; font-weight: 600; color: #dc2626;">{{ $item['row'] }}</td>
                                    <td style="padding: 8px 12px; font-size: 12px; color: #6b7280;">{{ implode(' | ', array_filter($item['data'])) }}</td>
                                    <td style="padding: 8px 12px; font-size: 12px; color: #dc2626;">{{ implode('، ', $item['errors']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- الصفوف الصحيحة --}}
            @if(!empty($this->valid_rows))
                <div style="background: white; border: 1px solid #86efac; border-radius: 12px; overflow: hidden;">
                    <div style="background: #f0fdf4; padding: 12px 16px; font-weight: 700; color: #166534; font-size: 13px; border-bottom: 1px solid #86efac;">
                        ✓ صفوف صحيحة جاهزة للاستيراد ({{ count($this->valid_rows) }})
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            @foreach(array_slice($this->valid_rows, 0, 5) as $item)
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 7px 12px; font-size: 12px; color: #059669;">صف {{ $item['row'] }}</td>
                                    <td style="padding: 7px 12px; font-size: 12px; color: #374151;">{{ implode(' | ', array_filter($item['data'])) }}</td>
                                </tr>
                            @endforeach
                            @if(count($this->valid_rows) > 5)
                                <tr>
                                    <td colspan="2" style="padding: 7px 12px; font-size: 12px; color: #9ca3af; text-align: center;">
                                        ... و {{ count($this->valid_rows) - 5 }} صف آخر
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endif

        @else
            {{-- حالة الانتظار --}}
            <div style="text-align: center; padding: 60px; color: #9ca3af; font-size: 14px; background: white; border: 1px solid #e5e7eb; border-radius: 12px;">
                <div style="font-size: 36px; margin-bottom: 12px;">📥</div>
                <div style="font-weight: 600; margin-bottom: 8px;">خطوات الاستيراد:</div>
                <div style="font-size: 13px; line-height: 2;">
                    1. حمّل القالب المناسب<br>
                    2. أضف البيانات في الملف<br>
                    3. ارفع الملف واضغط "تحقق من البيانات"<br>
                    4. راجع النتائج ثم اضغط "تأكيد الاستيراد"
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
