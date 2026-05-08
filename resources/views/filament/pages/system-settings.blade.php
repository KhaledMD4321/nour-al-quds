<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">

        {{-- ─── شريط التبويبات ────────────────────────────────────────────── --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex gap-1 overflow-x-auto">
                @foreach ($this->getTabs() as $key => $tab)
                    <button
                        wire:click="setActiveTab('{{ $key }}')"
                        @class([
                            'flex items-center gap-2 whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                            'border-amber-500 text-amber-600 dark:text-amber-400'
                                => $activeTab === $key,
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'
                                => $activeTab !== $key,
                        ])
                    >
                        <x-filament::icon :icon="$tab['icon']" class="w-4 h-4" />
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- ─── محتوى التبويب النشط ────────────────────────────────────────── --}}
        <form wire:submit.prevent="save" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">

                @php $settings = $this->getSettingsByGroup($activeTab); @endphp

                @if ($settings->isEmpty())
                    <p class="text-gray-400 text-center py-8">لا توجد إعدادات في هذه المجموعة.</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach ($settings as $setting)
                            <div @class(['md:col-span-2' => in_array($setting->type, ['textarea'])])>

                                {{-- ─── تسمية الحقل ─────────────────────────────── --}}
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $setting->label }}
                                </label>

                                {{-- ─── حقل text ────────────────────────────────── --}}
                                @if ($setting->type === 'text')
                                    <input
                                        type="text"
                                        wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition"
                                    />

                                {{-- ─── حقل number ──────────────────────────────── --}}
                                @elseif ($setting->type === 'number')
                                    <input
                                        type="number"
                                        wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition"
                                    />

                                {{-- ─── حقل textarea ────────────────────────────── --}}
                                @elseif ($setting->type === 'textarea')
                                    <textarea
                                        wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                        rows="3"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition resize-none"
                                    ></textarea>

                                {{-- ─── حقل toggle ──────────────────────────────── --}}
                                @elseif ($setting->type === 'toggle')
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <div class="relative">
                                            <input
                                                type="checkbox"
                                                wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                                value="1"
                                                class="sr-only peer"
                                            />
                                            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 rounded-full peer-checked:bg-amber-500 transition-colors"></div>
                                            <div class="absolute top-0.5 right-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:-translate-x-5"></div>
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ ($formData[$activeTab][$setting->key] ?? '0') == '1' ? 'مفعّل' : 'معطّل' }}
                                        </span>
                                    </label>

                                {{-- ─── حقل select ──────────────────────────────── --}}
                                @elseif ($setting->type === 'select')
                                    <select
                                        wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition"
                                    >
                                        @php $opts = is_array($setting->options) ? $setting->options : []; @endphp
                                        @foreach ($opts as $opt)
                                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>

                                {{-- ─── حقل color ───────────────────────────────── --}}
                                @elseif ($setting->type === 'color')
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="color"
                                            wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                            class="w-12 h-10 rounded border border-gray-300 dark:border-gray-600 cursor-pointer p-0.5"
                                        />
                                        <input
                                            type="text"
                                            wire:model="formData.{{ $activeTab }}.{{ $setting->key }}"
                                            placeholder="#1e40af"
                                            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition font-mono"
                                        />
                                    </div>

                                {{-- ─── حقل file (اللوجو) ───────────────────────── --}}
                                @elseif ($setting->type === 'file')
                                    <div class="space-y-3">
                                        {{-- عرض اللوجو الحالي --}}
                                        @if ($this->getLogoUrl())
                                            <div class="flex items-center gap-3">
                                                <img
                                                    src="{{ $this->getLogoUrl() }}"
                                                    alt="لوجو الشركة"
                                                    class="h-16 w-auto rounded border border-gray-200 dark:border-gray-600 p-1 bg-white"
                                                />
                                                <span class="text-xs text-gray-400">اللوجو الحالي</span>
                                            </div>
                                        @endif

                                        {{-- معاينة اللوجو الجديد قبل الرفع --}}
                                        @if ($logoUpload)
                                            <div class="flex items-center gap-3">
                                                <img
                                                    src="{{ $logoUpload->temporaryUrl() }}"
                                                    alt="معاينة"
                                                    class="h-16 w-auto rounded border border-amber-300 p-1 bg-white"
                                                />
                                                <span class="text-xs text-amber-500">سيتم رفعه عند الحفظ</span>
                                            </div>
                                        @endif

                                        <input
                                            type="file"
                                            wire:model="logoUpload"
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                                file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700
                                                hover:file:bg-amber-100 cursor-pointer"
                                        />
                                        <p class="text-xs text-gray-400">PNG, JPG, SVG — حجم أقصى 2 ميجابايت</p>
                                    </div>

                                @endif

                                {{-- وصف الحقل إن وجد --}}
                                @if ($setting->description)
                                    <p class="mt-1 text-xs text-gray-400">{{ $setting->description }}</p>
                                @endif

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ─── أزرار الحفظ / الإلغاء ─────────────────────────────────── --}}
            <div class="flex items-center justify-end gap-3">
                <button
                    type="button"
                    wire:click="reset"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                >
                    إلغاء التغييرات
                </button>

                <button
                    type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow transition"
                >
                    <span wire:loading.remove wire:target="save">حفظ الإعدادات</span>
                    <span wire:loading wire:target="save">جارِ الحفظ...</span>
                </button>
            </div>
        </form>

    </div>
</x-filament-panels::page>
