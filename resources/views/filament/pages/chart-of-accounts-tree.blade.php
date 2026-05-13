<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- شريط الأدوات --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; gap: 8px; align-items: center;">
                <button wire:click="toggleAddForm()"
                    style="background: #059669; color: white; padding: 8px 18px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    + إضافة حساب رئيسي
                </button>
                <button wire:click="$toggle('showAll')"
                    style="background: white; border: 1px solid #d1d5db; padding: 8px 14px; border-radius: 8px; font-size: 12px; cursor: pointer;">
                    {{ $showAll ? '🔽 طي الكل' : '▶ توسيع الكل' }}
                </button>
            </div>
            {{-- فلتر النوع --}}
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <button wire:click="$set('filterType', null)"
                    style="padding: 6px 12px; border-radius: 6px; font-size: 11px; cursor: pointer;
                           border: 1px solid {{ !$filterType ? '#1e40af' : '#e5e7eb' }};
                           background: {{ !$filterType ? '#1e40af' : 'white' }};
                           color: {{ !$filterType ? 'white' : '#374151' }};">
                    الكل
                </button>
                @foreach($this->getAccountTypes() as $typeKey => $typeInfo)
                    <button wire:click="$set('filterType', '{{ $typeKey }}')"
                        style="padding: 6px 12px; border-radius: 6px; font-size: 11px; cursor: pointer;
                               border: 1px solid {{ $filterType === $typeKey ? $typeInfo['color'] : '#e5e7eb' }};
                               background: {{ $filterType === $typeKey ? $typeInfo['color'] : 'white' }};
                               color: {{ $filterType === $typeKey ? 'white' : '#374151' }};">
                        {{ $typeInfo['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- إحصائيات --}}
        <div style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
            @foreach($this->getAccountTypes() as $typeKey => $typeInfo)
                @php $count = \App\Models\ChartOfAccount::where('type', $typeKey)->where('is_active', true)->count(); @endphp
                <div style="background: {{ $typeInfo['bg'] }}; border: 1px solid {{ $typeInfo['color'] }}33; border-radius: 8px; padding: 10px 16px; min-width: 110px; text-align: center;">
                    <div style="font-size: 22px; font-weight: 800; color: {{ $typeInfo['color'] }};">{{ $count }}</div>
                    <div style="font-size: 11px; color: {{ $typeInfo['color'] }};">{{ $typeInfo['label'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- فورم إضافة حساب جديد --}}
        @if($showAddForm)
            <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                <div style="font-weight: 700; margin-bottom: 12px; color: #166534;">
                    {{ $newAccount['parent_id'] ? 'إضافة حساب فرعي' : 'إضافة حساب رئيسي' }}
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr 150px 180px auto; gap: 10px; align-items: end;">
                    <div>
                        <label style="font-size: 11px; color: #6b7280; display: block; margin-bottom: 4px;">الكود</label>
                        <input type="text" wire:model.live="newAccount.code"
                            style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-family: monospace; font-size: 14px;" />
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #6b7280; display: block; margin-bottom: 4px;">اسم الحساب</label>
                        <input type="text" wire:model.live="newAccount.name"
                            style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;" />
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #6b7280; display: block; margin-bottom: 4px;">النوع</label>
                        <select wire:model.live="newAccount.type"
                            style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                            @foreach($this->getAccountTypes() as $k => $v)
                                <option value="{{ $k }}">{{ $v['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #6b7280; display: block; margin-bottom: 4px;">الوحدة</label>
                        <select wire:model.live="newAccount.business_unit_id"
                            style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                            <option value="">عام</option>
                            @foreach($this->getBusinessUnits() as $uid => $uname)
                                <option value="{{ $uid }}">{{ $uname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button wire:click="createAccount"
                            style="background: #059669; color: white; padding: 8px 14px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; white-space: nowrap;">حفظ</button>
                        <button wire:click="toggleAddForm"
                            style="background: white; border: 1px solid #d1d5db; padding: 8px 14px; border-radius: 6px; font-size: 12px; cursor: pointer; white-space: nowrap;">إلغاء</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- الشجرة --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            {{-- Header --}}
            <div style="display: grid; grid-template-columns: 1fr 100px 100px 100px 130px; background: #1e293b; color: white; padding: 10px 16px; font-size: 12px; font-weight: 600;">
                <div>الحساب</div>
                <div style="text-align: center;">النوع</div>
                <div style="text-align: center;">الوحدة</div>
                <div style="text-align: center;">الحركات</div>
                <div style="text-align: center;">إجراءات</div>
            </div>

            @forelse($this->getTree() as $node)
                @include('filament.pages.partials.account-tree-node', [
                    'node'    => $node,
                    'depth'   => 0,
                    'showAll' => $showAll,
                ])
            @empty
                <div style="padding: 40px; text-align: center; color: #9ca3af; font-size: 14px;">لا توجد حسابات</div>
            @endforelse
        </div>

    </div>
</x-filament-panels::page>
