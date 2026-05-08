<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" dir="rtl">

        {{-- ═══════════════════════════════════════════════════════════
             العمود الأيمن: قائمة الأدوار + إنشاء دور جديد
        ═══════════════════════════════════════════════════════════ --}}
        <div class="space-y-4">

            {{-- ─── إنشاء دور جديد ─────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">إنشاء دور جديد</h3>
                <div class="flex gap-2">
                    <input
                        type="text"
                        wire:model="newRoleName"
                        placeholder="اسم الدور (بالإنجليزي)"
                        class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                        dir="ltr"
                    />
                    <button
                        wire:click="createRole"
                        class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded-lg transition"
                        title="إنشاء"
                    >
                        <x-filament::icon icon="heroicon-o-plus" class="w-4 h-4" />
                    </button>
                </div>
                @error('newRoleName')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- ─── قائمة الأدوار ───────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                <div class="px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">الأدوار</h3>
                </div>

                @foreach ($this->getRoles() as $role)
                    @php
                        $isSystem    = $role->name === 'super_admin';
                        $isSelected  = $selectedRoleId === $role->id;
                    @endphp
                    <div
                        @class([
                            'flex items-center justify-between px-4 py-3 cursor-pointer transition',
                            'bg-amber-50 dark:bg-amber-900/20 border-r-2 border-amber-500' => $isSelected,
                            'hover:bg-gray-50 dark:hover:bg-gray-700/50'                   => ! $isSelected,
                        ])
                        wire:click="selectRole({{ $role->id }})"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200" dir="ltr">
                                {{ $role->name }}
                                @if ($isSystem)
                                    <span class="mr-1 text-xs bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 px-1.5 py-0.5 rounded">محمي</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $role->permissions_count }} صلاحية
                                &bull;
                                {{ $role->users_count }} مستخدم
                            </p>
                        </div>

                        @if (! $isSystem)
                            <button
                                wire:click.stop="deleteRole({{ $role->id }})"
                                class="text-gray-300 hover:text-red-500 transition"
                                title="حذف الدور"
                                wire:confirm="هل أنت متأكد من حذف هذا الدور؟"
                            >
                                <x-filament::icon icon="heroicon-o-trash" class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             العمودان الأيسران: الصلاحيات المجمّعة
        ═══════════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2">

            @if (! $selectedRoleId)
                <div class="flex flex-col items-center justify-center h-64 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 text-gray-400">
                    <x-filament::icon icon="heroicon-o-cursor-arrow-rays" class="w-10 h-10 mb-3" />
                    <p class="text-sm">اختر دوراً من القائمة لعرض صلاحياته وتعديلها</p>
                </div>

            @else
                @php $isSuperAdmin = $this->getSelectedRoleName() === 'super_admin'; @endphp

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">

                    {{-- رأس القسم --}}
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-gray-800 dark:text-gray-100" dir="ltr">
                            {{ $this->getSelectedRoleName() }}
                        </h2>

                        @if ($isSuperAdmin)
                            <span class="text-xs bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 px-3 py-1 rounded-full">
                                هذا الدور محمي — لا يمكن تعديله
                            </span>
                        @else
                            <button
                                wire:click="savePermissions"
                                class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition"
                            >
                                <span wire:loading.remove wire:target="savePermissions">حفظ الصلاحيات</span>
                                <span wire:loading wire:target="savePermissions">جارِ الحفظ...</span>
                            </button>
                        @endif
                    </div>

                    {{-- مجموعات الصلاحيات --}}
                    @foreach ($this->getGroupedPermissions() as $groupLabel => $permissions)
                        <div>
                            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 dark:border-gray-700 pb-1">
                                {{ $groupLabel }}
                            </h3>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach ($permissions as $permission)
                                    <label @class([
                                        'flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition',
                                        'opacity-60 cursor-not-allowed' => $isSuperAdmin,
                                    ])>
                                        <input
                                            type="checkbox"
                                            wire:model="selectedPermissions"
                                            value="{{ $permission->name }}"
                                            @disabled($isSuperAdmin)
                                            class="rounded border-gray-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500"
                                        />
                                        <span class="text-xs text-gray-700 dark:text-gray-300 font-mono" dir="ltr">
                                            {{ $permission->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
