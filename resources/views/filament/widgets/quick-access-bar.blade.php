<x-filament-widgets::widget>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ═══ الشريط الرئيسي ═══ --}}
        <div style="display: flex; align-items: flex-start; gap: 10px;">

            {{-- الأيقونات --}}
            <div style="display: flex; gap: 10px; flex-wrap: wrap; flex: 1;">
                @forelse($this->getActiveActions() as $key => $action)
                    <a href="{{ $action['url'] }}"
                       style="display: flex; flex-direction: column; align-items: center; justify-content: center;
                              width: 110px; height: 90px;
                              background: {{ $action['bg'] }};
                              border: 2px solid {{ $action['color'] }}33;
                              border-radius: 12px;
                              text-decoration: none;
                              cursor: pointer;"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 16px {{ $action['color'] }}33';"
                       onmouseout="this.style.transform=''; this.style.boxShadow='';">
                        <div style="font-size: 28px; margin-bottom: 4px;">{{ $action['icon'] }}</div>
                        <div style="font-size: 11px; font-weight: 700; color: {{ $action['color'] }}; text-align: center; line-height: 1.3; padding: 0 4px;">
                            {{ $action['label'] }}
                        </div>
                    </a>
                @empty
                    <div style="color: #9ca3af; font-size: 13px; padding: 24px 8px;">
                        اضغط ⚙️ لاختيار الاختصارات المفضلة
                    </div>
                @endforelse
            </div>

            {{-- زر التخصيص --}}
            <button wire:click="openCustomizer"
                style="background: white; border: 1px solid #e5e7eb; border-radius: 10px;
                       width: 44px; height: 44px; flex-shrink: 0; margin-top: 23px;
                       display: flex; align-items: center; justify-content: center;
                       cursor: pointer; font-size: 18px;"
                title="تخصيص الاختصارات">
                ⚙️
            </button>
        </div>

        {{-- ═══ نافذة التخصيص ═══ --}}
        @if($showCustomizer)
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                        background: rgba(0,0,0,0.45); z-index: 9999;
                        display: flex; align-items: center; justify-content: center;"
                 wire:click.self="closeCustomizer">

                <div style="background: white; border-radius: 16px;
                            width: 700px; max-width: 92vw; max-height: 82vh;
                            overflow-y: auto; padding: 24px;
                            box-shadow: 0 25px 50px rgba(0,0,0,0.25);"
                     dir="rtl">

                    {{-- رأس النافذة --}}
                    <div style="display: flex; justify-content: space-between; align-items: center;
                                margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2px solid #e5e7eb;">
                        <div>
                            <div style="font-size: 18px; font-weight: 800; color: #111827;">⚙️ تخصيص الاختصارات</div>
                            <div style="font-size: 12px; color: #9ca3af; margin-top: 3px;">
                                اختر الشاشات اللي عايزها تظهر في الشريط السريع
                            </div>
                        </div>
                        <button wire:click="closeCustomizer"
                            style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9ca3af; line-height: 1;">
                            ✕
                        </button>
                    </div>

                    {{-- عداد المختارين --}}
                    <div style="background: {{ count($tempSelection) > 0 ? '#dbeafe' : '#fef2f2' }};
                                padding: 8px 14px; border-radius: 8px; margin-bottom: 16px;
                                font-size: 12px; font-weight: 700;
                                color: {{ count($tempSelection) > 0 ? '#1e40af' : '#dc2626' }};">
                        {{ count($tempSelection) }} اختصار مختار
                        @if(count($tempSelection) > 10)
                            &nbsp;— <span style="color: #dc2626;">كتير أوي! الأفضل 6-8</span>
                        @endif
                    </div>

                    {{-- الاختصارات مجمّعة بالقسم --}}
                    @php
                        $grouped = collect($this->getAvailableActions())->groupBy('group');
                    @endphp

                    @foreach($grouped as $group => $actions)
                        <div style="margin-bottom: 18px;">
                            <div style="font-size: 12px; font-weight: 700; color: #1e40af;
                                        margin-bottom: 8px; padding-bottom: 4px;
                                        border-bottom: 1px solid #e5e7eb;">
                                {{ $group }}
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                @foreach($actions as $key => $action)
                                    @php $isSelected = in_array($key, $tempSelection); @endphp
                                    <button wire:click="toggleShortcut('{{ $key }}')"
                                        style="display: flex; align-items: center; gap: 8px;
                                               padding: 8px 14px; border-radius: 10px;
                                               border: 2px solid {{ $isSelected ? $action['color'] : '#e5e7eb' }};
                                               background: {{ $isSelected ? $action['bg'] : 'white' }};
                                               cursor: pointer;">
                                        <span style="font-size: 18px;">{{ $action['icon'] }}</span>
                                        <span style="font-size: 12px; font-weight: 600;
                                                     color: {{ $isSelected ? $action['color'] : '#6b7280' }};">
                                            {{ $action['label'] }}
                                        </span>
                                        @if($isSelected)
                                            <span style="font-size: 13px; color: {{ $action['color'] }};">✓</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- أزرار الحفظ --}}
                    <div style="display: flex; justify-content: space-between; align-items: center;
                                margin-top: 20px; padding-top: 16px; border-top: 2px solid #e5e7eb;">
                        <button wire:click="resetToDefaults"
                            style="background: white; border: 1px solid #d1d5db;
                                   padding: 10px 18px; border-radius: 8px;
                                   font-size: 12px; cursor: pointer; color: #6b7280;">
                            ↻ استعادة الافتراضي
                        </button>
                        <div style="display: flex; gap: 8px;">
                            <button wire:click="closeCustomizer"
                                style="background: white; border: 1px solid #d1d5db;
                                       padding: 10px 18px; border-radius: 8px;
                                       font-size: 12px; cursor: pointer;">
                                إلغاء
                            </button>
                            <button wire:click="saveCustomization"
                                style="background: #1e40af; color: white; border: none;
                                       padding: 10px 24px; border-radius: 8px;
                                       font-size: 13px; font-weight: 700; cursor: pointer;">
                                ✓ حفظ التخصيص
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        @endif

    </div>
</x-filament-widgets::widget>
