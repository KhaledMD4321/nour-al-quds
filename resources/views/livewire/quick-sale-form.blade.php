<div class="p-6 max-w-4xl mx-auto" dir="rtl">

    {{-- ══ Header ══ --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">⚡ بيع سريع</h1>
        <div class="text-sm text-gray-500">{{ now()->format('d/m/Y') }}</div>
    </div>

    {{-- ══ رسالة خطأ ══ --}}
    @if($errorMessage)
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg p-4 mb-4 text-sm">
            ⚠️ {{ $errorMessage }}
        </div>
    @endif

    {{-- ══ رسالة نجاح ══ --}}
    @if($saleCompleted)
        <div class="bg-green-50 border border-green-300 rounded-xl p-8 text-center">
            <div class="text-5xl mb-4">✅</div>
            <h2 class="text-2xl font-bold text-green-700 mb-2">تم البيع بنجاح!</h2>
            <p class="text-green-600 mb-6">
                الإيصال رقم:
                <strong class="text-lg">{{ \App\Models\QuickSale::find($lastSaleId)?->reference_number }}</strong>
            </p>
            <div class="flex gap-3 justify-center flex-wrap">
                <button wire:click="printReceipt"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                    🖨️ طباعة الإيصال
                </button>
                <button wire:click="newSale"
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition font-medium">
                    ➕ بيع جديد
                </button>
            </div>
        </div>

    @else

        {{-- ══ الإعدادات ══ --}}
        <div class="bg-white rounded-xl shadow-sm border p-4 mb-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الوحدة التشغيلية</label>
                    <select wire:model.live="businessUnitId"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\BusinessUnit::all() as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المخزن</label>
                    <select wire:model.live="warehouseId"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\Warehouse::where('is_active', true)->get() as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ══ البحث ══ --}}
        <div class="bg-white rounded-xl shadow-sm border p-4 mb-4 relative">
            <label class="block text-sm font-medium text-gray-700 mb-2">🔍 ابحث عن الصنف</label>
            <input wire:model.live="searchQuery"
                type="text"
                placeholder="اكتب جزء من اسم الصنف..."
                class="w-full border rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                autocomplete="off" />

            {{-- نتائج البحث --}}
            @if(count($searchResults) > 0)
                <div class="absolute z-50 bg-white border rounded-lg shadow-xl mt-1 max-h-64 overflow-y-auto"
                     style="right: 1rem; left: 1rem; top: 100%;">
                    @foreach($searchResults as $result)
                        <button wire:click="addProduct({{ $result['id'] }})"
                            class="w-full text-right px-4 py-3 hover:bg-blue-50 border-b last:border-0 transition">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-medium text-gray-800 text-sm">{{ $result['name'] }}</span>
                                    @if($result['code'])
                                        <span class="text-xs text-gray-400 mr-2">{{ $result['code'] }}</span>
                                    @endif
                                </div>
                                <div class="text-left mr-4 shrink-0">
                                    <div class="text-blue-600 font-bold text-sm">
                                        {{ number_format($result['price'], 2) }} ج.م
                                    </div>
                                    <div class="text-xs {{ $result['available'] > 0 ? 'text-green-600' : 'text-red-500' }}">
                                        متاح: {{ number_format($result['available'], 1) }}
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif

            @if(mb_strlen($searchQuery) >= 2 && count($searchResults) === 0)
                <div class="mt-2 text-sm text-gray-400">لا توجد نتائج لـ "{{ $searchQuery }}"</div>
            @endif
        </div>

        {{-- ══ السلة ══ --}}
        @if(count($items) > 0)
            <div class="bg-white rounded-xl shadow-sm border mb-4 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b">
                    <h3 class="font-semibold text-gray-700">🛒 الأصناف المضافة ({{ count($items) }})</h3>
                </div>

                <div class="divide-y">
                    @foreach($items as $index => $item)
                        <div class="px-4 py-3 flex items-center gap-3">

                            {{-- اسم الصنف --}}
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate">{{ $item['name'] }}</p>
                                <p class="text-xs {{ $item['available'] > 0 ? 'text-gray-400' : 'text-red-400' }}">
                                    متاح: {{ number_format($item['available'], 1) }}
                                </p>
                            </div>

                            {{-- الكمية --}}
                            <div class="flex items-center gap-1">
                                <button wire:click="decreaseQuantity({{ $index }})"
                                    class="w-7 h-7 rounded-full bg-gray-200 hover:bg-red-100 text-gray-600 font-bold text-sm flex items-center justify-center transition">
                                    −
                                </button>
                                <input wire:model.live.debounce.500ms="items.{{ $index }}.quantity"
                                    type="number" min="0.001" step="0.5"
                                    class="w-16 text-center border rounded px-1 py-1 text-sm" />
                                <button wire:click="increaseQuantity({{ $index }})"
                                    class="w-7 h-7 rounded-full bg-gray-200 hover:bg-green-100 text-gray-600 font-bold text-sm flex items-center justify-center transition">
                                    +
                                </button>
                            </div>

                            {{-- السعر --}}
                            <div class="w-28">
                                <input wire:model.live.debounce.500ms="items.{{ $index }}.unit_price"
                                    type="number" min="0" step="0.01"
                                    class="w-full border rounded px-2 py-1 text-sm text-center"
                                    placeholder="السعر" />
                            </div>

                            {{-- الإجمالي --}}
                            <div class="w-24 text-left font-bold text-blue-600 text-sm shrink-0">
                                {{ number_format($item['total'], 2) }} ج.م
                            </div>

                            {{-- حذف --}}
                            <button wire:click="removeItem({{ $index }})"
                                class="text-red-400 hover:text-red-600 transition text-xl leading-none shrink-0">
                                ✕
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- الإجمالي الكلي --}}
                <div class="bg-gray-50 px-4 py-4 border-t flex justify-between items-center">
                    <span class="font-bold text-gray-700 text-lg">الإجمالي</span>
                    <span class="font-bold text-blue-700 text-2xl">
                        {{ number_format($totalAmount, 2) }} ج.م
                    </span>
                </div>
            </div>

            {{-- ══ بيانات إضافية ══ --}}
            <div class="bg-white rounded-xl shadow-sm border p-4 mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اسم العميل (اختياري)</label>
                        <input wire:model="customerName" type="text"
                            placeholder="اسم العميل"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                        <input wire:model="notes" type="text"
                            placeholder="ملاحظة على الإيصال"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>
            </div>

            {{-- ══ زرار البيع ══ --}}
            <button wire:click="processSale"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-not-allowed"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl text-xl transition shadow-lg">
                <span wire:loading.remove wire:target="processSale">
                    💰 تأكيد البيع — {{ number_format($totalAmount, 2) }} ج.م
                </span>
                <span wire:loading wire:target="processSale">⏳ جاري التنفيذ...</span>
            </button>

        @else
            <div class="bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 p-12 text-center text-gray-400">
                <div class="text-4xl mb-3">🛒</div>
                <p class="text-base">ابحث عن أصناف وأضفها للسلة</p>
            </div>
        @endif

    @endif
</div>
