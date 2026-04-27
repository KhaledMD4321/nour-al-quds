<div dir="rtl" style="padding:16px; max-width:1400px; margin:0 auto; font-family:Cairo,sans-serif;">

    {{-- ══ رسالة نجاح ══ --}}
    @if($successMessage)
    <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px 16px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
        <span style="color:#166534; font-weight:600; font-size:14px;">✅ {{ $successMessage }}</span>
        <div style="display:flex; gap:8px;">
            @if($savedInvoiceId)
            <button wire:click="printPdf" type="button"
                style="background:#2563eb; color:white; border:none; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;">
                🖨️ طباعة PDF
            </button>
            @endif
            <button wire:click="resetForm" type="button"
                style="background:#16a34a; color:white; border:none; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;">
                ➕ فاتورة جديدة
            </button>
        </div>
    </div>
    @endif

    {{-- ══ رسالة خطأ ══ --}}
    @if($errorMessage)
    <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#991b1b; font-size:13px;">
        ⚠️ {{ $errorMessage }}
    </div>
    @endif

    {{-- ══ Header: بيانات الفاتورة الرئيسية ══ --}}
    <div style="background:white; border:1px solid #e5e7eb; border-radius:10px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr; gap:12px; align-items:end;">

            {{-- العميل --}}
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px;">
                    العميل <span style="color:#ef4444;">*</span>
                </label>
                <select wire:model.live="customerId"
                    style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:8px 10px; font-size:13px; background:white; color:#111827;">
                    <option value="0">— اختر العميل —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- المخزن --}}
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px;">المخزن</label>
                <select wire:model.live="warehouseId"
                    style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:8px 10px; font-size:13px; background:white; color:#111827;">
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- تاريخ الفاتورة --}}
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px;">التاريخ</label>
                <input wire:model="invoiceDate" type="date"
                    style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:8px 10px; font-size:13px; color:#111827; box-sizing:border-box;" />
            </div>

            {{-- نوع الدفع --}}
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px;">نوع الدفع</label>
                <select wire:model="paymentType"
                    style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:8px 10px; font-size:13px; background:white; color:#111827;">
                    @foreach($paymentTypes as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- الخصومات الافتراضية --}}
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px;">
                    خصومات افتراضية (%)
                </label>
                <div style="display:flex; gap:4px;">
                    <input wire:model.live="defaultD1" type="number" min="0" max="100" step="0.5"
                        placeholder="خ1"
                        style="width:33%; border:1px solid #d1d5db; border-radius:6px; padding:6px 4px; font-size:12px; text-align:center; box-sizing:border-box;" />
                    <input wire:model.live="defaultD2" type="number" min="0" max="100" step="0.5"
                        placeholder="خ2"
                        style="width:33%; border:1px solid #d1d5db; border-radius:6px; padding:6px 4px; font-size:12px; text-align:center; box-sizing:border-box;" />
                    <input wire:model.live="defaultD3" type="number" min="0" max="100" step="0.5"
                        placeholder="خ3"
                        style="width:33%; border:1px solid #d1d5db; border-radius:6px; padding:6px 4px; font-size:12px; text-align:center; box-sizing:border-box;" />
                </div>
            </div>
        </div>
    </div>

    {{-- ══ خصومات المصنّعين (تظهر فقط لما يكون في الفاتورة أصناف) ══ --}}
    @if(count($companyDiscounts) > 0)
    <div style="background:white; border:1px solid #dbeafe; border-radius:10px; padding:14px 16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">

        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <span style="font-weight:700; font-size:13px; color:#1d4ed8;">🏭 خصومات المصنّعين</span>
            <span style="font-size:12px; color:#6b7280;">— عدّل خصم مصنّع واضغط "تطبيق" ليتطبق على كل أصنافه في الفاتورة</span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#eff6ff; border-bottom:1px solid #dbeafe;">
                        <th style="padding:7px 12px; text-align:right; font-size:11px; color:#6b7280; font-weight:600;">المصنّع</th>
                        <th style="padding:7px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:120px;">خصم 1 %</th>
                        <th style="padding:7px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:120px;">خصم 2 %</th>
                        <th style="padding:7px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:120px;">خصم 3 %</th>
                        <th style="padding:7px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:90px;">الأصناف</th>
                        <th style="padding:7px 12px; text-align:center; font-size:11px; color:#6b7280; font-weight:600; width:90px;">تطبيق</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companyDiscounts as $companyId => $cd)
                    <tr style="border-bottom:1px solid #f3f4f6;">

                        {{-- اسم المصنّع --}}
                        <td style="padding:8px 12px; font-weight:700; color:#1d4ed8; font-size:13px;">
                            {{ $cd['name'] }}
                        </td>

                        {{-- خصم 1 --}}
                        <td style="padding:6px 8px;">
                            <input wire:model.live.debounce.300ms="companyDiscounts.{{ $companyId }}.d1"
                                type="number" min="0" max="100" step="0.5"
                                placeholder="0"
                                style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:13px; text-align:center; box-sizing:border-box;" />
                        </td>

                        {{-- خصم 2 --}}
                        <td style="padding:6px 8px;">
                            <input wire:model.live.debounce.300ms="companyDiscounts.{{ $companyId }}.d2"
                                type="number" min="0" max="100" step="0.5"
                                placeholder="0"
                                style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:13px; text-align:center; box-sizing:border-box;" />
                        </td>

                        {{-- خصم 3 --}}
                        <td style="padding:6px 8px;">
                            <input wire:model.live.debounce.300ms="companyDiscounts.{{ $companyId }}.d3"
                                type="number" min="0" max="100" step="0.5"
                                placeholder="0"
                                style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:13px; text-align:center; box-sizing:border-box;" />
                        </td>

                        {{-- عدد الأصناف --}}
                        <td style="padding:8px 12px; text-align:center;">
                            <span style="background:#eff6ff; color:#1d4ed8; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">
                                {{ collect($items)->where('company_id', $companyId)->count() }}
                            </span>
                        </td>

                        {{-- زرار تطبيق --}}
                        <td style="padding:6px 8px; text-align:center;">
                            <button wire:click="applyCompanyDiscount({{ $companyId }})" type="button"
                                style="background:#1d4ed8; color:white; border:none; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; white-space:nowrap;"
                                onmouseover="this.style.background='#1e40af'"
                                onmouseout="this.style.background='#1d4ed8'">
                                تطبيق ✓
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ══ الصفحة الرئيسية: لوحة الأصناف + جدول الفاتورة ══ --}}
    <div style="display:grid; grid-template-columns:360px 1fr; gap:12px; align-items:start;">

        {{-- ────────────────────────────────────────────────────────
             Panel اليمين: اختيار الأصناف
        ──────────────────────────────────────────────────────────── --}}
        <div style="background:white; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">

            {{-- فلتر المصنّع + البحث --}}
            <div style="padding:12px; border-bottom:1px solid #f3f4f6; background:#f9fafb;">
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">
                    فلتر بالمصنّع
                </label>
                <select wire:model.live="selectedCompanyId"
                    style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:7px 10px; font-size:13px; background:white; color:#111827; margin-bottom:8px;">
                    <option value="0">— كل المصنّعين —</option>
                    @foreach($companies as $co)
                        <option value="{{ $co->id }}">{{ $co->name }}</option>
                    @endforeach
                </select>

                <input wire:model.live.debounce.300ms="searchQuery"
                    type="text"
                    placeholder="🔍 ابحث باسم الصنف... (F2)"
                    id="product-search"
                    style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:7px 10px; font-size:13px; box-sizing:border-box; color:#111827;"
                    autocomplete="off" />
            </div>

            {{-- قائمة الأصناف --}}
            <div style="max-height:500px; overflow-y:auto;" id="product-list">
                @if(count($productList) === 0)
                    <div style="padding:32px 16px; text-align:center; color:#9ca3af; font-size:13px;">
                        @if($selectedCompanyId || mb_strlen($searchQuery) >= 2)
                            لا توجد أصناف مطابقة
                        @else
                            اختر مصنّع أو ابحث باسم الصنف
                        @endif
                    </div>
                @else
                    @foreach($productList as $idx => $product)
                    <div
                        wire:click="addProduct({{ $product['id'] }})"
                        data-product-id="{{ $product['id'] }}"
                        tabindex="{{ $idx + 1 }}"
                        style="padding:9px 12px; border-bottom:1px solid #f9fafb; cursor:pointer; display:flex; justify-content:space-between; align-items:center; {{ $product['in_cart'] ? 'background:#eff6ff;' : 'background:white;' }} transition:background 0.1s;"
                        onmouseover="this.style.background='{{ $product['in_cart'] ? '#dbeafe' : '#f9fafb' }}'"
                        onmouseout="this.style.background='{{ $product['in_cart'] ? '#eff6ff' : 'white' }}'">

                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:{{ $product['in_cart'] ? '700' : '500' }}; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ $product['name'] }}
                            </div>
                            <div style="font-size:11px; color:#9ca3af; margin-top:2px;">
                                {{ $product['code'] }}
                                @if($product['available'] > 0)
                                    · متاح: <span style="color:#16a34a;">{{ number_format($product['available'], 1) }}</span>
                                @else
                                    · <span style="color:#ef4444;">غير متاح</span>
                                @endif
                            </div>
                        </div>

                        <div style="text-align:left; margin-right:10px; flex-shrink:0;">
                            <div style="font-size:13px; font-weight:700; color:#1d4ed8;">
                                {{ number_format($product['price'], 2) }} ج.م
                            </div>
                            @if($product['in_cart'])
                            <div style="font-size:11px; background:#1d4ed8; color:white; border-radius:12px; padding:1px 8px; text-align:center; margin-top:2px;">
                                {{ $product['cart_qty'] }} ✓
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            {{-- تلميح الاختصارات --}}
            <div style="padding:8px 12px; background:#f9fafb; border-top:1px solid #f3f4f6; font-size:11px; color:#9ca3af; text-align:center;">
                Click أو Space للإضافة · انقر مجدداً لزيادة الكمية · F2 للبحث
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────
             Panel اليسار: جدول الفاتورة
        ──────────────────────────────────────────────────────────── --}}
        <div style="background:white; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">

            {{-- Header الجدول --}}
            <div style="padding:10px 16px; background:#f9fafb; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:700; font-size:14px; color:#111827;">
                    بنود الفاتورة
                    @if(count($items) > 0)
                    <span style="background:#1d4ed8; color:white; border-radius:20px; padding:1px 10px; font-size:12px; margin-right:6px;">
                        {{ count($items) }}
                    </span>
                    @endif
                </span>
                @if(count($items) > 0)
                <span style="font-size:12px; color:#6b7280;">
                    خ. افتراضي: {{ $defaultD1 }}% + {{ $defaultD2 }}% + {{ $defaultD3 }}%
                </span>
                @endif
            </div>

            @if(count($items) === 0)
            <div style="padding:60px 24px; text-align:center; color:#9ca3af;">
                <div style="font-size:40px; margin-bottom:10px;">📋</div>
                <div style="font-size:14px;">اختر أصناف من القائمة على اليمين</div>
            </div>
            @else

            {{-- جدول البنود --}}
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:12px;">
                    <thead>
                        <tr style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
                            <th style="padding:8px 8px; text-align:right; color:#6b7280; font-size:11px; white-space:nowrap; font-weight:600;">#</th>
                            <th style="padding:8px 10px; text-align:right; color:#6b7280; font-size:11px; font-weight:600;">الصنف</th>
                            <th style="padding:8px 6px; text-align:center; color:#6b7280; font-size:11px; width:85px; font-weight:600;">سعر اللستة</th>
                            <th style="padding:8px 4px; text-align:center; color:#6b7280; font-size:11px; width:56px; font-weight:600;">خ1%</th>
                            <th style="padding:8px 4px; text-align:center; color:#6b7280; font-size:11px; width:56px; font-weight:600;">خ2%</th>
                            <th style="padding:8px 4px; text-align:center; color:#6b7280; font-size:11px; width:56px; font-weight:600;">خ3%</th>
                            <th style="padding:8px 6px; text-align:center; color:#6b7280; font-size:11px; width:80px; font-weight:600;">سعر الوحدة</th>
                            <th style="padding:8px 6px; text-align:center; color:#6b7280; font-size:11px; width:70px; font-weight:600;">الكمية</th>
                            <th style="padding:8px 10px; text-align:center; color:#6b7280; font-size:11px; width:90px; font-weight:600;">الإجمالي</th>
                            <th style="padding:8px 4px; width:28px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        <tr style="border-bottom:1px solid #f3f4f6; {{ $i % 2 !== 0 ? 'background:#fafafa;' : '' }}">

                            <td style="padding:5px 8px; color:#9ca3af; font-size:11px; text-align:center;">{{ $i + 1 }}</td>

                            <td style="padding:5px 10px; font-weight:500; color:#111827; max-width:180px;">
                                <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item['name'] }}</div>
                                @if((float)$item['available'] < (float)$item['quantity'])
                                    <div style="color:#ef4444; font-size:10px;">⚠️ يتجاوز المتاح ({{ number_format($item['available'], 1) }})</div>
                                @endif
                            </td>

                            {{-- سعر اللستة --}}
                            <td style="padding:3px 4px;">
                                <input wire:model.live.debounce.400ms="items.{{ $i }}.list_price"
                                    type="number" min="0" step="0.01"
                                    style="width:100%; border:1px solid #e5e7eb; border-radius:4px; padding:4px 5px; font-size:12px; text-align:center; color:#111827; box-sizing:border-box;" />
                            </td>

                            {{-- خصم 1 --}}
                            <td style="padding:3px 3px;">
                                <input wire:model.live.debounce.400ms="items.{{ $i }}.d1"
                                    type="number" min="0" max="100" step="0.5"
                                    style="width:100%; border:1px solid #e5e7eb; border-radius:4px; padding:4px 3px; font-size:12px; text-align:center; box-sizing:border-box;" />
                            </td>

                            {{-- خصم 2 --}}
                            <td style="padding:3px 3px;">
                                <input wire:model.live.debounce.400ms="items.{{ $i }}.d2"
                                    type="number" min="0" max="100" step="0.5"
                                    style="width:100%; border:1px solid #e5e7eb; border-radius:4px; padding:4px 3px; font-size:12px; text-align:center; box-sizing:border-box;" />
                            </td>

                            {{-- خصم 3 --}}
                            <td style="padding:3px 3px;">
                                <input wire:model.live.debounce.400ms="items.{{ $i }}.d3"
                                    type="number" min="0" max="100" step="0.5"
                                    style="width:100%; border:1px solid #e5e7eb; border-radius:4px; padding:4px 3px; font-size:12px; text-align:center; box-sizing:border-box;" />
                            </td>

                            {{-- سعر الوحدة (محسوب — للعرض فقط) --}}
                            <td style="padding:5px 6px; text-align:center; color:#1d4ed8; font-weight:700; font-size:12px; white-space:nowrap;">
                                {{ number_format($item['unit_price'], 2) }}
                            </td>

                            {{-- الكمية --}}
                            <td style="padding:3px 4px;">
                                <input wire:model.live.debounce.400ms="items.{{ $i }}.quantity"
                                    type="number" min="0.001" step="0.5"
                                    style="width:100%; border:1px solid #e5e7eb; border-radius:4px; padding:4px 5px; font-size:12px; text-align:center; font-weight:700; box-sizing:border-box;" />
                            </td>

                            {{-- الإجمالي --}}
                            <td style="padding:5px 10px; text-align:center; font-weight:700; font-size:13px; color:#111827; white-space:nowrap;">
                                {{ number_format($item['total'], 2) }}
                            </td>

                            {{-- حذف --}}
                            <td style="padding:5px 4px; text-align:center;">
                                <button wire:click="removeItem({{ $i }})" type="button"
                                    style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:16px; padding:2px 5px; line-height:1;"
                                    onmouseover="this.style.color='#dc2626'"
                                    onmouseout="this.style.color='#ef4444'"
                                    title="حذف">✕</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- شريط الإجماليات --}}
            <div style="padding:12px 16px; border-top:2px solid #e5e7eb; background:#f9fafb; display:flex; align-items:center; gap:28px; flex-wrap:wrap;">
                <div style="text-align:center;">
                    <div style="font-size:11px; color:#6b7280; margin-bottom:2px;">مجموع اللستة</div>
                    <div style="font-size:14px; font-weight:600; color:#374151;">
                        {{ number_format($subtotal, 2) }} ج.م
                    </div>
                </div>
                @if($discountAmount > 0)
                <div style="text-align:center;">
                    <div style="font-size:11px; color:#6b7280; margin-bottom:2px;">إجمالي الخصم</div>
                    <div style="font-size:14px; font-weight:600; color:#dc2626;">
                        − {{ number_format($discountAmount, 2) }} ج.م
                    </div>
                </div>
                @endif
                <div style="margin-right:auto; text-align:center;">
                    <div style="font-size:11px; color:#6b7280; margin-bottom:2px;">الإجمالي الكلي</div>
                    <div style="font-size:24px; font-weight:800; color:#1d4ed8;">
                        {{ number_format($totalAmount, 2) }} ج.م
                    </div>
                </div>
            </div>

            {{-- أزرار الحفظ + ملاحظات --}}
            <div style="padding:12px 16px; border-top:1px solid #e5e7eb; display:flex; gap:10px; align-items:center;">

                <input wire:model="notes" type="text"
                    placeholder="ملاحظات على الفاتورة..."
                    style="flex:1; border:1px solid #d1d5db; border-radius:6px; padding:9px 12px; font-size:13px; color:#111827;" />

                <button wire:click="saveDraft" type="button"
                    wire:loading.attr="disabled"
                    style="background:#f59e0b; color:white; border:none; padding:10px 18px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; box-shadow:0 2px 6px rgba(245,158,11,0.3);"
                    onmouseover="this.style.background='#d97706'"
                    onmouseout="this.style.background='#f59e0b'">
                    <span wire:loading.remove wire:target="saveDraft">💾 حفظ مسودة</span>
                    <span wire:loading wire:target="saveDraft">⏳ جاري الحفظ...</span>
                </button>

                <button wire:click="confirmInvoice" type="button"
                    wire:loading.attr="disabled"
                    style="background:#16a34a; color:white; border:none; padding:10px 22px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; white-space:nowrap; box-shadow:0 2px 8px rgba(22,163,74,0.3);"
                    onmouseover="this.style.background='#15803d'"
                    onmouseout="this.style.background='#16a34a'">
                    <span wire:loading.remove wire:target="confirmInvoice">✅ تأكيد الفاتورة</span>
                    <span wire:loading wire:target="confirmInvoice">⏳ جاري التأكيد...</span>
                </button>
            </div>

            @endif {{-- end: count($items) > 0 --}}
        </div>
    </div>

</div>

{{-- ══ Keyboard Shortcuts ══ --}}
<script>
(function () {
    // Space أو Enter على بطاقة صنف = إضافة
    document.addEventListener('keydown', function (e) {
        if ((e.code === 'Space' || e.code === 'Enter') && e.target.dataset.productId) {
            e.preventDefault();
            const id  = parseInt(e.target.dataset.productId);
            const cmp = window.Livewire.find(
                document.querySelector('[wire\\:id]').getAttribute('wire:id')
            );
            if (cmp) cmp.call('addProduct', id);
        }
    });

    // F2 = focus البحث
    document.addEventListener('keydown', function (e) {
        if (e.code === 'F2') {
            e.preventDefault();
            const s = document.getElementById('product-search');
            if (s) s.focus();
        }
    });

    // Escape في البحث = مسح
    document.addEventListener('keydown', function (e) {
        if (e.code === 'Escape') {
            const s = document.getElementById('product-search');
            if (s && document.activeElement === s) {
                s.value = '';
                s.dispatchEvent(new Event('input'));
            }
        }
    });
}());
</script>
