<x-filament-panels::page>
<div dir="rtl" style="font-family: Cairo, sans-serif; max-width: 960px;">

    {{-- ══ شريط التبويبات المحسّن ══ --}}
    @php
        $tabIcons = [
            'company'        => '🏢',
            'invoice'        => '📄',
            'numbering'      => '🔢',
            'defaults'       => '⚙️',
            'alerts'         => '🔔',
            'print'          => '🖨️',
            'business_rules' => '⚖️',
        ];
    @endphp

    <div style="display: flex; gap: 4px; margin-bottom: 20px; background: #f3f4f6;
                padding: 5px; border-radius: 14px; flex-wrap: wrap;">
        @foreach($this->getTabs() as $key => $tab)
            <button wire:click="setActiveTab('{{ $key }}')" type="button"
                style="display: flex; align-items: center; gap: 7px;
                       padding: 9px 16px; border-radius: 10px; font-size: 12px; font-weight: 600;
                       cursor: pointer; border: none; transition: all 0.2s; white-space: nowrap;
                       background: {{ $activeTab === $key ? 'white' : 'transparent' }};
                       color: {{ $activeTab === $key ? '#1e40af' : '#6b7280' }};
                       box-shadow: {{ $activeTab === $key ? '0 1px 4px rgba(0,0,0,0.12)' : 'none' }};">
                <span style="font-size: 15px;">{{ $tabIcons[$key] ?? '⚙️' }}</span>
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- ══ المحتوى ══ --}}
    <form wire:submit.prevent="save">

        @php $settings = $this->getSettingsByGroup($activeTab); @endphp

        <div style="background: white; border-radius: 14px; border: 1px solid #e5e7eb;
                    box-shadow: 0 1px 4px rgba(0,0,0,0.05); overflow: hidden;">

            {{-- رأس القسم --}}
            <div style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;
                        padding: 14px 20px; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;">{{ $tabIcons[$activeTab] ?? '⚙️' }}</span>
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #111827;">
                        {{ $this->getTabs()[$activeTab]['label'] ?? $activeTab }}
                    </div>
                    <div style="font-size: 11px; color: #9ca3af; margin-top: 1px;">
                        {{ $settings->count() }} إعداد في هذه المجموعة
                    </div>
                </div>
            </div>

            <div style="padding: 20px;">

                @if ($settings->isEmpty())
                    <div style="text-align: center; padding: 40px; color: #9ca3af; font-size: 13px;">
                        لا توجد إعدادات في هذه المجموعة
                    </div>
                @else
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
                        @foreach ($settings as $setting)
                            @php
                                $isFullWidth = in_array($setting->type, ['textarea', 'file']);
                            @endphp
                            <div style="{{ $isFullWidth ? 'grid-column: 1 / -1;' : '' }}">

                                {{-- تسمية الحقل --}}
                                @if($setting->type !== 'toggle')
                                    <label style="display: block; font-size: 12px; font-weight: 600;
                                                  color: #374151; margin-bottom: 6px;">
                                        {{ $setting->label }}
                                    </label>
                                @endif

                                {{-- ── حقل text ── --}}
                                @if ($setting->type === 'text')
                                    <input type="text"
                                        wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px;
                                               padding: 9px 12px; font-size: 13px; color: #111827;
                                               background: #fff; outline: none; box-sizing: border-box;
                                               transition: border-color 0.2s;"
                                        onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'"
                                        onblur="this.style.borderColor='#d1d5db';this.style.boxShadow='none'" />

                                {{-- ── حقل number ── --}}
                                @elseif ($setting->type === 'number')
                                    <input type="number"
                                        wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px;
                                               padding: 9px 12px; font-size: 13px; color: #111827;
                                               background: #fff; outline: none; box-sizing: border-box;"
                                        onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'"
                                        onblur="this.style.borderColor='#d1d5db';this.style.boxShadow='none'" />

                                {{-- ── حقل textarea ── --}}
                                @elseif ($setting->type === 'textarea')
                                    <textarea
                                        wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                        rows="3"
                                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px;
                                               padding: 9px 12px; font-size: 13px; color: #111827;
                                               background: #fff; outline: none; resize: vertical; box-sizing: border-box;"
                                        onfocus="this.style.borderColor='#3b82f6'"
                                        onblur="this.style.borderColor='#d1d5db'"></textarea>

                                {{-- ── حقل toggle (switch) ── --}}
                                @elseif ($setting->type === 'toggle')
                                    @php $isOn = ($formData[$activeTab][$setting->key] ?? '0') == '1'; @endphp
                                    <div style="display: flex; align-items: center; gap: 14px; padding: 12px 14px;
                                                background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px;">
                                        <label style="position: relative; display: inline-block; width: 46px; height: 24px; cursor: pointer; flex-shrink: 0;">
                                            <input type="checkbox"
                                                wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                                value="1"
                                                style="opacity: 0; width: 0; height: 0; position: absolute;"
                                                class="peer" />
                                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;
                                                        background: {{ $isOn ? '#1e40af' : '#d1d5db' }};
                                                        border-radius: 12px; transition: background 0.3s;"
                                                 class="peer-checked:bg-blue-700"></div>
                                            <div style="position: absolute; top: 3px;
                                                        {{ $isOn ? 'left: 25px;' : 'left: 3px;' }}
                                                        width: 18px; height: 18px; background: white; border-radius: 50%;
                                                        transition: left 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.25);"></div>
                                        </label>
                                        <div>
                                            <div style="font-size: 12px; font-weight: 600; color: #374151;">
                                                {{ $setting->label }}
                                            </div>
                                            <div style="font-size: 11px; color: {{ $isOn ? '#1e40af' : '#9ca3af' }}; margin-top: 1px;">
                                                {{ $isOn ? '✓ مفعّل' : '✗ معطّل' }}
                                            </div>
                                        </div>
                                    </div>

                                {{-- ── حقل select ── --}}
                                @elseif ($setting->type === 'select')
                                    <select
                                        wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px;
                                               padding: 9px 12px; font-size: 13px; color: #111827;
                                               background: #fff; outline: none; box-sizing: border-box;">
                                        @php $opts = is_array($setting->options) ? $setting->options : []; @endphp
                                        @foreach ($opts as $opt)
                                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>

                                {{-- ── حقل color ── --}}
                                @elseif ($setting->type === 'color')
                                    @php $currentColor = $formData[$activeTab][$setting->key] ?? '#1e40af'; @endphp
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <input type="color"
                                            wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                            style="width: 48px; height: 40px; border: 2px solid #e5e7eb;
                                                   border-radius: 8px; cursor: pointer; padding: 2px;
                                                   background: #fff;" />
                                        <div style="width: 36px; height: 36px; border-radius: 8px; border: 2px solid #e5e7eb;
                                                    background: {{ $currentColor }}; flex-shrink: 0;"></div>
                                        <input type="text"
                                            wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                            placeholder="#1e40af"
                                            style="flex: 1; border: 1px solid #d1d5db; border-radius: 8px;
                                                   padding: 9px 12px; font-size: 13px; color: #111827;
                                                   background: #fff; outline: none; font-family: monospace;" />
                                    </div>

                                {{-- ── حقل file (اللوجو) ── --}}
                                @elseif ($setting->type === 'file')
                                    <div>
                                        {{-- عرض اللوجو الحالي --}}
                                        @if ($this->getLogoUrl())
                                            <div style="margin-bottom: 12px; padding: 12px 14px; background: #f9fafb;
                                                        border: 1px solid #e5e7eb; border-radius: 10px;
                                                        display: inline-flex; align-items: center; gap: 12px;">
                                                <img src="{{ $this->getLogoUrl() }}" alt="اللوجو الحالي"
                                                    style="max-height: 64px; max-width: 160px; border-radius: 6px; border: 1px solid #e5e7eb;" />
                                                <span style="font-size: 11px; color: #9ca3af;">اللوجو الحالي</span>
                                            </div>
                                        @endif

                                        {{-- معاينة اللوجو الجديد قبل الرفع --}}
                                        @if ($logoUpload)
                                            <div style="margin-bottom: 12px; padding: 12px 14px; background: #eff6ff;
                                                        border: 1px solid #93c5fd; border-radius: 10px;
                                                        display: inline-flex; align-items: center; gap: 12px;">
                                                <img src="{{ $logoUpload->temporaryUrl() }}" alt="معاينة"
                                                    style="max-height: 64px; max-width: 160px; border-radius: 6px;" />
                                                <span style="font-size: 11px; color: #1e40af; font-weight: 600;">
                                                    ✓ سيتم رفعه عند الحفظ
                                                </span>
                                            </div>
                                        @endif

                                        <input type="file" wire:model="logoUpload" accept="image/*"
                                            style="display: block; width: 100%; font-size: 12px; padding: 10px 12px;
                                                   border: 2px dashed #d1d5db; border-radius: 8px; background: #fafafa;
                                                   cursor: pointer; box-sizing: border-box; color: #374151;" />
                                        <div style="font-size: 10px; color: #9ca3af; margin-top: 5px;">
                                            PNG, JPG, SVG — حجم أقصى 2 ميجابايت
                                        </div>
                                    </div>

                                @endif

                                {{-- وصف الحقل --}}
                                @if ($setting->description)
                                    <p style="margin-top: 4px; font-size: 10.5px; color: #9ca3af;">
                                        {{ $setting->description }}
                                    </p>
                                @endif

                            </div>
                        @endforeach
                    </div>
                @endif

            </div>{{-- end padding --}}

        </div>{{-- end card --}}

        {{-- ══ أزرار الحفظ ══ --}}
        <div style="margin-top: 20px; padding: 16px 20px; background: white;
                    border: 1px solid #e5e7eb; border-radius: 12px;
                    display: flex; justify-content: space-between; align-items: center;
                    box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
            <button type="submit" wire:loading.attr="disabled"
                style="background: #1e40af; color: white; padding: 11px 36px; border-radius: 10px;
                       font-size: 14px; font-weight: 700; border: none; cursor: pointer;
                       box-shadow: 0 4px 12px rgba(30,64,175,0.3);">
                <span wire:loading.remove wire:target="save">✓ حفظ الإعدادات</span>
                <span wire:loading wire:target="save">⏳ جاري الحفظ...</span>
            </button>
            <button type="button" wire:click="discardChanges"
                style="background: white; border: 1px solid #d1d5db; padding: 10px 22px;
                       border-radius: 8px; font-size: 12px; cursor: pointer; color: #6b7280;
                       font-family: Cairo, sans-serif;">
                ↻ إلغاء التغييرات
            </button>
        </div>

    </form>

</div>
</x-filament-panels::page>
