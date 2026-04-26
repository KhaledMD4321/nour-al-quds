<div style="padding:24px; max-width:860px; margin:0 auto; direction:rtl; font-family:inherit;">

    {{-- ══ Header ══ --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1 style="font-size:22px; font-weight:700; color:#1f2937; margin:0;">⚡ بيع سريع</h1>
        <div style="font-size:13px; color:#6b7280;">{{ now()->format('d/m/Y') }}</div>
    </div>

    {{-- ══ رسالة خطأ ══ --}}
    @if($errorMessage)
        <div style="background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
            ⚠️ {{ $errorMessage }}
        </div>
    @endif

    {{-- ══ رسالة نجاح ══ --}}
    @if($saleCompleted)
        <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:12px; padding:40px 24px; text-align:center;">
            <div style="font-size:48px; margin-bottom:12px;">✅</div>
            <h2 style="font-size:20px; font-weight:700; color:#15803d; margin:0 0 8px;">تم البيع بنجاح!</h2>
            <p style="color:#16a34a; margin:0 0 24px;">
                الإيصال رقم:
                <strong style="font-size:16px;">{{ \App\Models\QuickSale::find($lastSaleId)?->reference_number }}</strong>
            </p>
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <button wire:click="printReceipt" type="button"
                    style="background:#2563eb; color:#fff; padding:10px 24px; border-radius:8px; border:none; cursor:pointer; font-size:14px; font-weight:600;">
                    🖨️ طباعة الإيصال
                </button>
                <button wire:click="newSale" type="button"
                    style="background:#16a34a; color:#fff; padding:10px 24px; border-radius:8px; border:none; cursor:pointer; font-size:14px; font-weight:600;">
                    ➕ بيع جديد
                </button>
            </div>
        </div>

    @else

        {{-- ══ الإعدادات ══ --}}
        <div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:16px; margin-bottom:16px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:4px;">الوحدة التشغيلية</label>
                    <select wire:model.live="businessUnitId"
                        style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; color:#111827; background:#fff;">
                        @foreach(\App\Models\BusinessUnit::all() as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:4px;">المخزن</label>
                    <select wire:model.live="warehouseId"
                        style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; color:#111827; background:#fff;">
                        @foreach(\App\Models\Warehouse::where('is_active', true)->get() as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ══ البحث ══ --}}
        <div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:16px; margin-bottom:16px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:8px;">🔍 ابحث عن الصنف</label>
            <input wire:model.live="searchQuery"
                type="text"
                placeholder="اكتب جزء من اسم الصنف..."
                style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:12px 16px; font-size:13px; color:#111827; outline:none; box-sizing:border-box;"
                autocomplete="off" />

            {{-- نتائج البحث --}}
            @if(count($searchResults) > 0)
                <div style="margin-top:8px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                    @foreach($searchResults as $result)
                        <button wire:click="addProduct({{ $result['id'] }})"
                            type="button"
                            style="display:block; width:100%; text-align:right; padding:10px 16px; border-bottom:1px solid #f3f4f6; background:#fff; cursor:pointer; border-left:none; border-right:none; border-top:none;"
                            onmouseover="this.style.background='#eff6ff'"
                            onmouseout="this.style.background='#fff'">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div style="text-align:right;">
                                    <span style="font-weight:600; color:#1f2937; font-size:13px;">{{ $result['name'] }}</span>
                                    @if($result['code'])
                                        <span style="color:#9ca3af; font-size:11px; margin-right:8px;">{{ $result['code'] }}</span>
                                    @endif
                                </div>
                                <div style="text-align:left; flex-shrink:0; margin-right:16px;">
                                    <div style="color:#2563eb; font-weight:700; font-size:13px;">
                                        {{ number_format($result['price'], 2) }} ج.م
                                    </div>
                                    <div style="font-size:11px; color:{{ $result['available'] > 0 ? '#16a34a' : '#dc2626' }};">
                                        متاح: {{ number_format($result['available'], 1) }}
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif

            @if(mb_strlen($searchQuery) >= 2 && count($searchResults) === 0)
                <div style="margin-top:8px; font-size:13px; color:#9ca3af;">لا توجد نتائج لـ "{{ $searchQuery }}"</div>
            @endif
        </div>

        {{-- ══ السلة ══ --}}
        @if(count($items) > 0)
            <div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; margin-bottom:16px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">

                {{-- رأس السلة --}}
                <div style="background:#f9fafb; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
                    <h3 style="font-size:14px; font-weight:600; color:#374151; margin:0;">🛒 الأصناف المضافة ({{ count($items) }})</h3>
                </div>

                {{-- بنود السلة --}}
                @foreach($items as $index => $item)
                    <div style="padding:12px 16px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:12px;">

                        {{-- اسم الصنف --}}
                        <div style="flex:1; min-width:0; overflow:hidden;">
                            <p style="font-weight:600; color:#1f2937; font-size:13px; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ $item['name'] }}
                            </p>
                            <p style="font-size:11px; color:{{ $item['available'] > 0 ? '#9ca3af' : '#ef4444' }}; margin:2px 0 0;">
                                متاح: {{ number_format($item['available'], 1) }}
                            </p>
                        </div>

                        {{-- الكمية --}}
                        <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                            <button wire:click="decreaseQuantity({{ $index }})" type="button"
                                style="width:28px; height:28px; border-radius:50%; background:#e5e7eb; border:none; cursor:pointer; font-weight:700; font-size:16px; display:flex; align-items:center; justify-content:center; color:#374151; line-height:1;">
                                −
                            </button>
                            <input wire:model.live.debounce.500ms="items.{{ $index }}.quantity"
                                type="number" min="0.001" step="0.5"
                                style="width:60px; text-align:center; border:1px solid #d1d5db; border-radius:6px; padding:4px; font-size:13px;" />
                            <button wire:click="increaseQuantity({{ $index }})" type="button"
                                style="width:28px; height:28px; border-radius:50%; background:#e5e7eb; border:none; cursor:pointer; font-weight:700; font-size:16px; display:flex; align-items:center; justify-content:center; color:#374151; line-height:1;">
                                +
                            </button>
                        </div>

                        {{-- السعر --}}
                        <div style="flex-shrink:0; width:110px;">
                            <input wire:model.live.debounce.500ms="items.{{ $index }}.unit_price"
                                type="number" min="0" step="0.01"
                                style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:4px 8px; font-size:13px; text-align:center; box-sizing:border-box;"
                                placeholder="السعر" />
                        </div>

                        {{-- الإجمالي --}}
                        <div style="flex-shrink:0; width:90px; text-align:left; font-weight:700; color:#2563eb; font-size:14px;">
                            {{ number_format($item['total'], 2) }} ج.م
                        </div>

                        {{-- حذف --}}
                        <button wire:click="removeItem({{ $index }})" type="button"
                            style="flex-shrink:0; background:none; border:none; cursor:pointer; color:#f87171; font-size:18px; line-height:1; padding:4px;"
                            onmouseover="this.style.color='#dc2626'"
                            onmouseout="this.style.color='#f87171'">
                            ✕
                        </button>
                    </div>
                @endforeach

                {{-- الإجمالي الكلي --}}
                <div style="background:#f9fafb; padding:16px; border-top:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-weight:700; color:#374151; font-size:16px;">الإجمالي</span>
                    <span style="font-weight:700; color:#1d4ed8; font-size:24px;">
                        {{ number_format($totalAmount, 2) }} ج.م
                    </span>
                </div>
            </div>

            {{-- ══ بيانات إضافية ══ --}}
            <div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:16px; margin-bottom:16px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:4px;">اسم العميل (اختياري)</label>
                        <input wire:model="customerName" type="text"
                            placeholder="اسم العميل"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; box-sizing:border-box;" />
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:4px;">ملاحظات</label>
                        <input wire:model="notes" type="text"
                            placeholder="ملاحظة على الإيصال"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; box-sizing:border-box;" />
                    </div>
                </div>
            </div>

            {{-- ══ زرار البيع ══ --}}
            <button wire:click="processSale" type="button"
                wire:loading.attr="disabled"
                style="width:100%; background:#16a34a; color:#fff; font-weight:700; padding:18px; border-radius:12px; border:none; cursor:pointer; font-size:18px; box-shadow:0 4px 12px rgba(22,163,74,0.3);">
                <span wire:loading.remove wire:target="processSale">
                    💰 تأكيد البيع — {{ number_format($totalAmount, 2) }} ج.م
                </span>
                <span wire:loading wire:target="processSale">⏳ جاري التنفيذ...</span>
            </button>

        @else
            <div style="background:#f9fafb; border-radius:12px; border:2px dashed #e5e7eb; padding:60px 24px; text-align:center; color:#9ca3af;">
                <div style="font-size:40px; margin-bottom:12px;">🛒</div>
                <p style="font-size:14px; margin:0;">ابحث عن أصناف وأضفها للسلة</p>
            </div>
        @endif

    @endif
</div>
