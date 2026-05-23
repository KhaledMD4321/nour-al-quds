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

    {{-- ═══════════════════════════════════════════════════════════════
         منطقة الخطر — حذف البيانات (super_admin فقط)
    ════════════════════════════════════════════════════════════════ --}}
    @if(auth()->user()->isSuperAdmin())
    <div style="background:#fef2f2; border:2px solid #fca5a5; border-radius:12px;
                padding:24px; margin-top:32px;">

        <div style="font-size:17px; font-weight:800; color:#dc2626; margin-bottom:20px;">
            ⚠️ منطقة خطرة — حذف البيانات
        </div>

        {{-- ── حذف محدد ── --}}
        <div style="font-size:13px; font-weight:600; color:#374151; margin-bottom:10px;">
            اختر ما تريد حذفه:
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
                    gap:8px; margin-bottom:20px;">
            @foreach($this->getDeleteTargets() as $key => $target)
                <button wire:click="$set('deleteTarget','{{ $key }}')"
                    style="display:flex; justify-content:space-between; align-items:center;
                           padding:10px 14px; border-radius:8px; cursor:pointer; text-align:right;
                           border:1.5px solid {{ $deleteTarget === $key ? '#dc2626' : '#e5e7eb' }};
                           background:{{ $deleteTarget === $key ? '#fee2e2' : 'white' }};
                           transition:all .15s;">
                    <span style="font-size:12px; color:#374151;">{{ $target['label'] }}</span>
                    <span style="background:#fee2e2; color:#dc2626; padding:2px 9px;
                                 border-radius:999px; font-size:11px; font-weight:700; margin-right:8px;">
                        {{ number_format($target['count']) }}
                    </span>
                </button>
            @endforeach
        </div>

        @if($deleteTarget)
        <div style="background:white; border:1px solid #fca5a5; border-radius:8px;
                    padding:16px; margin-bottom:16px;">
            <div style="font-size:12px; color:#dc2626; margin-bottom:10px; font-weight:600;">
                اكتب <strong style="background:#fee2e2;padding:1px 6px;border-radius:4px;">تأكيد الحذف</strong> بالضبط ثم اضغط حذف:
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <input type="text" wire:model.live="confirmText"
                       placeholder='اكتب "تأكيد الحذف"'
                       style="flex:1; padding:9px 12px; border:1.5px solid #fca5a5;
                              border-radius:6px; font-size:13px; font-family:Cairo,sans-serif;" />
                <button wire:click="deleteData"
                    style="background:#dc2626; color:white; padding:9px 20px;
                           border:none; border-radius:6px; font-size:13px;
                           font-weight:700; cursor:pointer; white-space:nowrap;">
                    🗑 حذف نهائي
                </button>
                <button wire:click="$set('deleteTarget','')"
                    style="background:#f3f4f6; color:#6b7280; padding:9px 14px;
                           border:none; border-radius:6px; font-size:12px; cursor:pointer;">
                    إلغاء
                </button>
            </div>
        </div>
        @endif

        {{-- ── فاصل ── --}}
        <div style="height:1px; background:#fca5a5; margin:24px 0;"></div>

        {{-- ── إعادة تعيين كامل ── --}}
        <div style="font-size:13px; font-weight:700; color:#991b1b; margin-bottom:6px;">
            💣 إعادة تعيين النظام بالكامل
        </div>
        <div style="font-size:11.5px; color:#6b7280; margin-bottom:14px; line-height:1.6;">
            سيتم حذف: كل الفواتير، المقبوضات، المدفوعات، الشيكات، القيود، حركات المخزون، حركات الخزائن.<br>
            <strong>لن يتم حذف:</strong> الأصناف، العملاء، الموردين، شجرة الحسابات، الإعدادات.
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <input type="text" wire:model.live="resetConfirm"
                   placeholder='اكتب "إعادة تعيين النظام"'
                   style="width:290px; padding:9px 12px; border:2px solid #dc2626;
                          border-radius:6px; font-size:13px; font-family:Cairo,sans-serif;" />
            <button wire:click="fullReset"
                style="background:#7f1d1d; color:white; padding:9px 20px;
                       border:none; border-radius:6px; font-size:13px;
                       font-weight:800; cursor:pointer; white-space:nowrap;">
                💣 إعادة تعيين كامل
            </button>
        </div>
    </div>
    @endif

</x-filament-panels::page>
