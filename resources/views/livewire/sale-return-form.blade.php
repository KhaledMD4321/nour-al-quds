<div dir="rtl" style="font-family:Cairo,sans-serif;">

    {{-- ══ رسالة خطأ ══ --}}
    @if($errorMessage)
    <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:12px 16px; margin-bottom:12px; color:#991b1b; font-size:13px;">
        ⚠️ {{ $errorMessage }}
    </div>
    @endif

    {{-- ══ رسالة نجاح ══ --}}
    @if($successMessage)
    <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px 16px; margin-bottom:12px; color:#166534; font-size:13px; display:flex; justify-content:space-between; align-items:center;">
        <span>✅ {{ $successMessage }}</span>
        <a href="/admin/invoices"
           style="background:#16a34a; color:white; padding:6px 14px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
            عرض الفواتير
        </a>
    </div>

    @elseif(count($returnItems) === 0)

    {{-- ══ تنبيه: لا يوجد قابل للإرجاع ══ --}}
    <div style="background:#fef9c3; border:1px solid #fde047; border-radius:8px; padding:16px; text-align:center; color:#854d0e; font-size:13px;">
        ⚠️ كل أصناف هذه الفاتورة تم إرجاعها مسبقاً
    </div>

    @else

    {{-- ══ جدول الأصناف ══ --}}
    <div style="background:white; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">

        <div style="padding:10px 14px; background:#f9fafb; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-weight:700; font-size:14px; color:#111827;">أصناف الفاتورة</span>
            <button wire:click="selectAll" type="button"
                style="background:#f59e0b; color:white; border:none; padding:5px 14px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;"
                onmouseover="this.style.background='#d97706'"
                onmouseout="this.style.background='#f59e0b'">
                تحديد الكل ⬆
            </button>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
                        <th style="padding:8px 12px; text-align:right; font-size:11px; color:#6b7280; font-weight:600;">الصنف</th>
                        <th style="padding:8px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:100px;">الكمية الأصلية</th>
                        <th style="padding:8px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:100px;">مرتجع سابق</th>
                        <th style="padding:8px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:100px;">الحد الأقصى</th>
                        <th style="padding:8px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:110px;">كمية المرتجع</th>
                        <th style="padding:8px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:95px;">سعر الوحدة</th>
                        <th style="padding:8px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:95px;">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returnItems as $i => $item)
                    <tr style="border-bottom:1px solid #f3f4f6; {{ $item['return_qty'] > 0 ? 'background:#fff7ed;' : '' }}">

                        <td style="padding:8px 12px; font-weight:500; color:#111827;">
                            {{ $item['product_name'] }}
                        </td>

                        <td style="padding:8px 12px; text-align:center; color:#6b7280;">
                            {{ number_format($item['original_qty'], 2) }}
                        </td>

                        <td style="padding:8px 12px; text-align:center; color:#dc2626;">
                            {{ $item['already_returned'] > 0 ? number_format($item['already_returned'], 2) : '—' }}
                        </td>

                        <td style="padding:8px 12px; text-align:center; font-weight:700; color:#1d4ed8;">
                            {{ number_format($item['max_returnable'], 2) }}
                        </td>

                        <td style="padding:5px 8px;">
                            <input wire:model.live.debounce.300ms="returnItems.{{ $i }}.return_qty"
                                type="number"
                                min="0"
                                max="{{ $item['max_returnable'] }}"
                                step="0.5"
                                style="width:100%; border:2px solid {{ $item['return_qty'] > 0 ? '#f59e0b' : '#e5e7eb' }}; border-radius:6px; padding:5px 8px; font-size:13px; text-align:center; font-weight:700; box-sizing:border-box;"
                                onfocus="this.select()" />
                        </td>

                        <td style="padding:8px 12px; text-align:center; color:#374151;">
                            {{ number_format($item['unit_price'], 2) }}
                        </td>

                        <td style="padding:8px 12px; text-align:center; font-weight:700; color:{{ $item['total'] > 0 ? '#dc2626' : '#9ca3af' }};">
                            {{ $item['total'] > 0 ? '- ' . number_format($item['total'], 2) : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- الإجمالي --}}
        <div style="padding:12px 16px; border-top:2px solid #e5e7eb; background:#fef2f2; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-weight:700; color:#374151; font-size:14px;">إجمالي المرتجع</span>
            <span style="font-size:22px; font-weight:800; color:#dc2626;">
                - {{ number_format($this->totalReturnAmount, 2) }} ج.م
            </span>
        </div>
    </div>

    {{-- ملاحظات + زرار تأكيد --}}
    <div style="display:flex; gap:10px; align-items:center;">
        <input wire:model="notes" type="text"
            placeholder="سبب المرتجع (اختياري)..."
            style="flex:1; border:1px solid #d1d5db; border-radius:8px; padding:10px 14px; font-size:13px; color:#111827;" />

        <button wire:click="submit" wire:loading.attr="disabled" type="button"
            style="background:#dc2626; color:white; border:none; padding:10px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; white-space:nowrap; box-shadow:0 2px 8px rgba(220,38,38,0.3);"
            onmouseover="this.style.background='#b91c1c'"
            onmouseout="this.style.background='#dc2626'">
            <span wire:loading.remove wire:target="submit">↩️ تأكيد المرتجع</span>
            <span wire:loading wire:target="submit">⏳ جاري التسجيل...</span>
        </button>
    </div>

    @endif
</div>
