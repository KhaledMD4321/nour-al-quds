<x-filament-panels::page>

    {{-- ═══════════════════════════════════════════════
         الفورم — يظهر فقط لو مش في وضع المعاينة
    ═══════════════════════════════════════════════ --}}
    @if (!$showPreview)

        <form wire:submit.prevent="previewFile" class="space-y-6">

            {{-- Company selector + FileUpload via Filament Schema --}}
            {{ $this->form }}

            {{-- Format guide --}}
            <div class="rounded-lg bg-info-50 dark:bg-info-900/20 p-4 text-sm text-info-800 dark:text-info-200 border border-info-200 dark:border-info-800">
                <p class="font-semibold mb-2">📋 تنسيق الملف المطلوب:</p>
                <div class="grid grid-cols-2 gap-1 font-mono text-xs">
                    <span class="font-bold">العمود A:</span> <span>كود الصنف (اختياري — يتولّد تلقائياً لو فاضي)</span>
                    <span class="font-bold">العمود B:</span> <span>اسم الصنف (مطلوب)</span>
                    <span class="font-bold">العمود C:</span> <span>السعر (مطلوب — رقم موجب)</span>
                    <span class="font-bold">العمود D:</span> <span>وحدة القياس (اختياري — الافتراضي: piece)</span>
                </div>
                <p class="mt-2 text-xs text-info-600 dark:text-info-400">الصف الأول = عناوين الأعمدة (يُتجاهل تلقائياً)</p>
            </div>

            {{-- Submit button --}}
            <div class="flex justify-end">
                <x-filament::button
                    type="submit"
                    color="primary"
                    icon="heroicon-o-eye"
                    wire:loading.attr="disabled"
                    wire:target="previewFile"
                >
                    <span wire:loading.remove wire:target="previewFile">معاينة الملف</span>
                    <span wire:loading wire:target="previewFile">جاري القراءة…</span>
                </x-filament::button>
            </div>

        </form>

    @endif

    {{-- ═══════════════════════════════════════════════
         المعاينة — يظهر بعد قراءة الملف
    ═══════════════════════════════════════════════ --}}
    @if ($showPreview && !empty($preview))

        <div class="space-y-6">

            {{-- ── ملخص ── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                <x-filament::section>
                    <div class="text-center py-2">
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $preview['summary']['total_rows'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">إجمالي الصفوف</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center py-2">
                        <div class="text-3xl font-bold text-success-600 dark:text-success-400">
                            {{ $preview['summary']['new_products'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">أصناف جديدة</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center py-2">
                        <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                            {{ $preview['summary']['existing'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">تحديث سعر</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center py-2">
                        <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">
                            {{ $preview['summary']['invalid'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">صفوف مرفوضة</div>
                    </div>
                </x-filament::section>

            </div>

            {{-- ── أصناف جديدة ── --}}
            @if (!empty($preview['new_products']))
                <x-filament::section collapsible>
                    <x-slot name="heading">
                        <span class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-plus-circle" class="h-5 w-5 text-success-500" />
                            أصناف جديدة هتتضاف ({{ count($preview['new_products']) }})
                        </span>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-right">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-2 font-medium">الصف</th>
                                    <th class="px-4 py-2 font-medium">الاسم</th>
                                    <th class="px-4 py-2 font-medium">السعر</th>
                                    <th class="px-4 py-2 font-medium">الوحدة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($preview['new_products'] as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-2 text-gray-400">{{ $item['row'] }}</td>
                                        <td class="px-4 py-2 font-medium">{{ $item['name'] }}</td>
                                        <td class="px-4 py-2 text-success-700 dark:text-success-400 font-bold">
                                            {{ number_format($item['price'], 2) }} ج.م.
                                        </td>
                                        <td class="px-4 py-2 text-gray-500">
                                            {{ \App\Models\LookupType::getLabel('unit_of_measure', $item['unit']) ?? $item['unit'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            {{-- ── أصناف موجودة — تحديث سعر ── --}}
            @if (!empty($preview['existing_products']))
                <x-filament::section collapsible>
                    <x-slot name="heading">
                        <span class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 text-primary-500" />
                            أصناف موجودة — تحديث السعر ({{ count($preview['existing_products']) }})
                        </span>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-right">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-2 font-medium">الكود</th>
                                    <th class="px-4 py-2 font-medium">الاسم</th>
                                    <th class="px-4 py-2 font-medium">السعر القديم</th>
                                    <th class="px-4 py-2 font-medium">السعر الجديد</th>
                                    <th class="px-4 py-2 font-medium">الفرق</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($preview['existing_products'] as $item)
                                    @php
                                        $diff      = $item['new_price'] - ($item['old_price'] ?? 0);
                                        $diffClass = $diff > 0
                                            ? 'text-danger-600 dark:text-danger-400'
                                            : ($diff < 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-400');
                                        $diffSign  = $diff > 0 ? '+' : '';
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $item['code'] }}</td>
                                        <td class="px-4 py-2 font-medium">{{ $item['name'] }}</td>
                                        <td class="px-4 py-2 text-gray-400">
                                            {{ $item['old_price'] !== null ? number_format($item['old_price'], 2) . ' ج.م.' : '—' }}
                                        </td>
                                        <td class="px-4 py-2 font-bold">{{ number_format($item['new_price'], 2) }} ج.م.</td>
                                        <td class="px-4 py-2 font-bold {{ $diffClass }}">
                                            {{ $diffSign }}{{ number_format($diff, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            {{-- ── صفوف مرفوضة ── --}}
            @if (!empty($preview['invalid_rows']))
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">
                        <span class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5 text-danger-500" />
                            صفوف مرفوضة — مش هتتحفظ ({{ count($preview['invalid_rows']) }})
                        </span>
                    </x-slot>

                    <div class="space-y-2">
                        @foreach ($preview['invalid_rows'] as $item)
                            <div class="rounded-lg bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 px-4 py-2 text-sm text-danger-700 dark:text-danger-300">
                                {{ $item['error'] }}
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            {{-- ── أزرار التأكيد والإلغاء ── --}}
            <div class="flex gap-3 justify-end pt-2">

                <x-filament::button
                    color="gray"
                    wire:click="cancelImport"
                    icon="heroicon-o-arrow-uturn-right"
                >
                    رجوع للرفع
                </x-filament::button>

                <x-filament::button
                    color="success"
                    wire:click="confirmImport"
                    icon="heroicon-o-check-circle"
                    wire:loading.attr="disabled"
                    wire:target="confirmImport"
                    wire:confirm="هل أنت متأكد؟ هيتم إنشاء إصدار جديد وأرشفة القديم."
                >
                    <span wire:loading.remove wire:target="confirmImport">
                        تأكيد الرفع وإنشاء الإصدار الجديد
                    </span>
                    <span wire:loading wire:target="confirmImport">
                        جاري الحفظ…
                    </span>
                </x-filament::button>

            </div>

        </div>

    @endif

</x-filament-panels::page>
