<x-filament-panels::page>
<div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; direction: rtl; font-family: Cairo, sans-serif; align-items: start;">

    {{-- ══════════════════════════════════════════
         العمود الأيمن: الأدوار + إنشاء دور
    ══════════════════════════════════════════ --}}
    <div style="display: flex; flex-direction: column; gap: 14px;">

        {{-- ─── إنشاء دور جديد ─── --}}
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb;
                    padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 10px;">
                ➕ إنشاء دور جديد
            </div>
            <div style="display: flex; gap: 8px;">
                <input type="text" wire:model="newRoleName"
                    placeholder="اسم الدور (بالإنجليزي)"
                    dir="ltr"
                    style="flex: 1; border: 1px solid #d1d5db; border-radius: 8px;
                           padding: 8px 12px; font-size: 12px; color: #111827;
                           background: #fff; outline: none; font-family: monospace;" />
                <button wire:click="createRole" type="button"
                    style="background: #1e40af; color: white; border: none; border-radius: 8px;
                           padding: 8px 14px; font-size: 18px; cursor: pointer; line-height: 1;">
                    +
                </button>
            </div>
            @error('newRoleName')
                <p style="margin-top: 6px; font-size: 11px; color: #dc2626;">{{ $message }}</p>
            @enderror
        </div>

        {{-- ─── قائمة الأدوار ─── --}}
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="background: #f9fafb; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                <div style="font-size: 13px; font-weight: 700; color: #374151;">🛡️ الأدوار</div>
            </div>

            @foreach ($this->getRoles() as $role)
                @php
                    $isSystem   = $role->name === 'super_admin';
                    $isSelected = $selectedRoleId === $role->id;
                @endphp
                <div wire:click="selectRole({{ $role->id }})"
                    style="display: flex; align-items: center; justify-content: space-between;
                           padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f3f4f6;
                           background: {{ $isSelected ? '#eff6ff' : 'white' }};
                           border-right: {{ $isSelected ? '3px solid #1e40af' : '3px solid transparent' }};
                           transition: background 0.15s;">
                    <div>
                        <div style="font-size: 12px; font-weight: 700;
                                    color: {{ $isSelected ? '#1e40af' : '#1f2937' }};" dir="ltr">
                            {{ $role->name }}
                            @if ($isSystem)
                                <span style="display: inline-block; margin-right: 6px; font-size: 9px;
                                             background: #fee2e2; color: #dc2626; padding: 1px 7px;
                                             border-radius: 10px; font-family: Cairo, sans-serif;
                                             font-weight: 700;">محمي</span>
                            @endif
                        </div>
                        <div style="font-size: 10.5px; color: #9ca3af; margin-top: 2px;">
                            {{ $role->permissions_count }} صلاحية
                            &bull;
                            {{ $role->users_count }} مستخدم
                        </div>
                    </div>

                    @if (! $isSystem)
                        <button wire:click.stop="deleteRole({{ $role->id }})" type="button"
                            wire:confirm="هل أنت متأكد من حذف هذا الدور؟"
                            style="background: none; border: none; cursor: pointer; color: #d1d5db;
                                   font-size: 16px; line-height: 1; padding: 4px;"
                            onmouseover="this.style.color='#dc2626'"
                            onmouseout="this.style.color='#d1d5db'">
                            ✕
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

    </div>

    {{-- ══════════════════════════════════════════
         العمود الأيسر: الصلاحيات
    ══════════════════════════════════════════ --}}
    <div>

        @if (! $selectedRoleId)
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;
                        height: 320px; background: white; border-radius: 14px;
                        border: 2px dashed #e5e7eb; color: #9ca3af;">
                <div style="font-size: 40px; margin-bottom: 12px;">🛡️</div>
                <p style="font-size: 13px;">اختر دوراً من القائمة لعرض صلاحياته وتعديلها</p>
            </div>

        @else
            @php $isSuperAdmin = $this->getSelectedRoleName() === 'super_admin'; @endphp

            <div style="background: white; border-radius: 14px; border: 1px solid #e5e7eb;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.05); overflow: hidden;">

                {{-- رأس الصلاحيات --}}
                <div style="background: {{ $isSuperAdmin ? '#fef2f2' : '#f9fafb' }};
                            border-bottom: 1px solid {{ $isSuperAdmin ? '#fecaca' : '#e5e7eb' }};
                            padding: 14px 20px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="font-size: 15px; font-weight: 800; color: #111827;" dir="ltr">
                            {{ $this->getSelectedRoleName() }}
                        </div>
                        @if ($isSuperAdmin)
                            <span style="font-size: 10px; background: #fee2e2; color: #dc2626;
                                         padding: 2px 10px; border-radius: 12px; font-weight: 700;">
                                🔒 هذا الدور محمي — لا يمكن تعديله
                            </span>
                        @endif
                    </div>

                    @if (! $isSuperAdmin)
                        <button wire:click="savePermissions" type="button"
                            style="background: #1e40af; color: white; border: none; border-radius: 9px;
                                   padding: 9px 24px; font-size: 13px; font-weight: 700; cursor: pointer;
                                   box-shadow: 0 3px 8px rgba(30,64,175,0.25); font-family: Cairo, sans-serif;">
                            <span wire:loading.remove wire:target="savePermissions">✓ حفظ الصلاحيات</span>
                            <span wire:loading wire:target="savePermissions">⏳ جاري الحفظ...</span>
                        </button>
                    @endif
                </div>

                {{-- مجموعات الصلاحيات --}}
                <div style="padding: 16px;">
                    @foreach ($this->getGroupedPermissions() as $groupLabel => $permissions)
                        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px;
                                    padding: 14px 16px; margin-bottom: 12px;">

                            {{-- عنوان المجموعة + العدد --}}
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <span style="background: #dbeafe; color: #1e40af; font-size: 10px; font-weight: 700;
                                             padding: 2px 8px; border-radius: 10px;">
                                    {{ $permissions->count() }}
                                </span>
                                <div style="font-size: 13px; font-weight: 700; color: #1e40af;">
                                    {{ $groupLabel }}
                                </div>
                            </div>

                            {{-- شبكة الصلاحيات --}}
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(195px, 1fr)); gap: 6px;">
                                @foreach ($permissions as $permission)
                                    @php $isSelected = in_array($permission->name, $selectedPermissions); @endphp
                                    <label style="display: flex; align-items: center; gap: 8px;
                                                  padding: 8px 10px; border-radius: 8px; cursor: {{ $isSuperAdmin ? 'not-allowed' : 'pointer' }};
                                                  background: {{ $isSelected ? '#eff6ff' : 'white' }};
                                                  border: 1px solid {{ $isSelected ? '#93c5fd' : '#e5e7eb' }};
                                                  opacity: {{ $isSuperAdmin ? '0.7' : '1' }};
                                                  transition: all 0.15s;">
                                        <input type="checkbox"
                                            wire:model="selectedPermissions"
                                            value="{{ $permission->name }}"
                                            @if($isSuperAdmin) disabled checked @endif
                                            style="width: 15px; height: 15px; accent-color: #1e40af;
                                                   cursor: {{ $isSuperAdmin ? 'not-allowed' : 'pointer' }}; flex-shrink: 0;" />
                                        <span style="font-size: 11.5px;
                                                     color: {{ $isSelected ? '#1e40af' : '#4b5563' }};
                                                     font-weight: {{ $isSelected ? '600' : '400' }};">
                                            {{ $this->getPermissionLabel($permission->name) }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                        </div>
                    @endforeach
                </div>

            </div>
        @endif

    </div>

</div>
</x-filament-panels::page>
