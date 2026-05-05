<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── إحصائيات ── --}}
        @php $stats = $this->getStats(); @endphp
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px;">
            @foreach([
                ['label' => 'عملاء مكررون',      'value' => $stats['duplicate_customers'],  'color' => '#dc2626', 'bg' => '#fef2f2'],
                ['label' => 'أصناف مكررة',        'value' => $stats['duplicate_products'],   'color' => '#dc2626', 'bg' => '#fef2f2'],
                ['label' => 'عملاء مؤرشفون',     'value' => $stats['archived_customers'],   'color' => '#6b7280', 'bg' => '#f3f4f6'],
                ['label' => 'أصناف مؤرشفة',      'value' => $stats['archived_products'],    'color' => '#6b7280', 'bg' => '#f3f4f6'],
                ['label' => 'موردون مؤرشفون',    'value' => $stats['archived_suppliers'],   'color' => '#6b7280', 'bg' => '#f3f4f6'],
                ['label' => 'أصناف بمخزون صفر',  'value' => $stats['zero_stock_products'],  'color' => '#d97706', 'bg' => '#fffbeb'],
            ] as $card)
            <div style="background: {{ $card['bg'] }}; border-radius: 10px; padding: 14px; text-align: center;">
                <div style="font-size: 11px; color: {{ $card['color'] }}; font-weight: 600; margin-bottom: 4px;">{{ $card['label'] }}</div>
                <div style="font-size: 22px; font-weight: 700; color: {{ $card['color'] }};">{{ number_format($card['value']) }}</div>
            </div>
            @endforeach
        </div>

        {{-- ── فلتر الأرشفة ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <div style="font-weight: 700; font-size: 14px; margin-bottom: 12px; color: #374151;">أرشفة البيانات غير النشطة</div>
            {{ $this->form }}
        </div>

        {{-- ── العملاء المكررون ── --}}
        @php $dupCustomers = $this->getDuplicateCustomers(); @endphp
        @if($dupCustomers->isNotEmpty())
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
            <div style="background: #fef2f2; padding: 12px 16px; font-weight: 700; color: #991b1b; font-size: 13px; border-bottom: 1px solid #fca5a5;">
                عملاء مكررون ({{ $dupCustomers->count() }} مجموعة)
            </div>
            @foreach($dupCustomers as $group)
                <div style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6;">
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
                        {{ $group['type'] === 'name' ? 'تكرار بالاسم' : 'تكرار بالتليفون' }}: <strong>{{ $group['key'] }}</strong>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @foreach($group['customers'] as $customer)
                            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; font-size: 12px;">
                                <strong>{{ $customer->code }}</strong> — {{ $customer->name }}
                                <br><span style="color: #9ca3af;">{{ $customer->phone }}</span>
                                <br>
                                @foreach($group['customers'] as $other)
                                    @if($other->id !== $customer->id)
                                        <button wire:click="mergeCustomers({{ $customer->id }}, {{ $other->id }})"
                                            wire:confirm="دمج '{{ $other->code }}' في '{{ $customer->code }}'؟"
                                            style="font-size:10px; background:#1e40af; color:white; border:none; border-radius:4px; padding:2px 8px; cursor:pointer; margin-top:4px;">
                                            دمج ← {{ $other->code }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- ── الأصناف المكررة ── --}}
        @php $dupProducts = $this->getDuplicateProducts(); @endphp
        @if($dupProducts->isNotEmpty())
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
            <div style="background: #fef2f2; padding: 12px 16px; font-weight: 700; color: #991b1b; font-size: 13px; border-bottom: 1px solid #fca5a5;">
                أصناف مكررة بالاسم ({{ $dupProducts->count() }} مجموعة)
            </div>
            @foreach($dupProducts as $group)
                <div style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6;">
                    <div style="font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ $group['name'] }}</div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @foreach($group['products'] as $product)
                            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; font-size: 12px;">
                                <strong>{{ $product->code }}</strong>
                                @if($product->company) <span style="color:#6b7280;">— {{ $product->company->name }}</span> @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        @if($dupCustomers->isEmpty() && $dupProducts->isEmpty())
        <div style="text-align: center; padding: 40px; color: #9ca3af; background: white; border: 1px solid #e5e7eb; border-radius: 12px;">
            <div style="font-size: 36px; margin-bottom: 12px;">✨</div>
            لا توجد تكرارات مكتشفة في قاعدة البيانات
        </div>
        @endif

    </div>
</x-filament-panels::page>
