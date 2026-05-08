<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManager extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-shield-check';
    protected static string|\UnitEnum|null   $navigationGroup = 'الإدارة';
    protected static ?int                    $navigationSort  = 91;
    protected static ?string                 $title           = 'إدارة الأدوار والصلاحيات';
    protected static ?string                 $navigationLabel = 'الأدوار والصلاحيات';
    protected string                         $view            = 'filament.pages.role-manager';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    // ─── State ────────────────────────────────────────────────────────────────────

    /** الدور المحدد حالياً للتعديل */
    public ?int $selectedRoleId = null;

    /** الصلاحيات المحددة للدور الحالي */
    public array $selectedPermissions = [];

    /** اسم الدور الجديد (حقل الإنشاء) */
    public string $newRoleName = '';

    // ─── Computed helpers ─────────────────────────────────────────────────────────

    /** قائمة الأدوار مع عدد الصلاحيات والمستخدمين */
    public function getRoles(): \Illuminate\Support\Collection
    {
        return Role::withCount(['permissions', 'users'])->orderBy('id')->get();
    }

    /** الصلاحيات مجمّعة حسب الفئة */
    public function getGroupedPermissions(): array
    {
        $groups = [
            'المبيعات'          => 'sales.',
            'المخزون'           => 'inventory.',
            'الخزينة'           => 'finance.treasury',
            'سندات القبض'       => 'finance.receipt',
            'سندات الصرف'       => 'finance.payment',
            'الشيكات'           => 'finance.cheque',
            'المحاسبة'          => 'accounting.',
            'التقارير'          => 'reports.',
            'الإعدادات'         => 'settings.',
            'أخرى'              => '',
        ];

        $all      = Permission::orderBy('name')->get();
        $result   = [];
        $assigned = collect();

        foreach ($groups as $label => $prefix) {
            if ($prefix === '') continue; // أخرى يُعالج في الأخير

            $perms = $all->filter(fn ($p) => str_starts_with($p->name, $prefix));
            if ($perms->isNotEmpty()) {
                $result[$label] = $perms->values();
                $assigned = $assigned->merge($perms->pluck('id'));
            }
        }

        // البقية (أخرى — view_any_receipt... إلخ)
        $others = $all->whereNotIn('id', $assigned->unique()->toArray());
        if ($others->isNotEmpty()) {
            $result['أخرى (Filament)'] = $others->values();
        }

        return $result;
    }

    /** تحديد دور للتعديل */
    public function selectRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;

        $role = Role::with('permissions')->find($roleId);
        $this->selectedPermissions = $role
            ? $role->permissions->pluck('name')->toArray()
            : [];
    }

    /** حفظ صلاحيات الدور */
    public function savePermissions(): void
    {
        if (! $this->selectedRoleId) return;

        $role = Role::find($this->selectedRoleId);
        if (! $role) return;

        // حماية super_admin من التعديل
        if ($role->name === 'super_admin') {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن تعديل صلاحيات دور super_admin.')
                ->danger()
                ->send();
            return;
        }

        $role->syncPermissions($this->selectedPermissions);

        // مسح كاش الصلاحيات
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Notification::make()
            ->title('تم الحفظ')
            ->body("تم حفظ صلاحيات الدور «{$role->name}» بنجاح.")
            ->success()
            ->send();
    }

    /** إنشاء دور جديد */
    public function createRole(): void
    {
        $this->validate(['newRoleName' => 'required|min:3|unique:roles,name']);

        $role = Role::create(['name' => $this->newRoleName, 'guard_name' => 'web']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->newRoleName = '';
        $this->selectRole($role->id);

        Notification::make()
            ->title('تم الإنشاء')
            ->body("تم إنشاء الدور «{$role->name}» بنجاح.")
            ->success()
            ->send();
    }

    /** حذف دور (soft / مباشر — بعد التأكيد) */
    public function deleteRole(int $roleId): void
    {
        $role = Role::find($roleId);
        if (! $role) return;

        if (in_array($role->name, ['super_admin', 'showroom_manager', 'distribution_manager'])) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن حذف الأدوار الأساسية للنظام.')
                ->danger()
                ->send();
            return;
        }

        if ($role->users()->count() > 0) {
            Notification::make()
                ->title('لا يمكن الحذف')
                ->body("الدور «{$role->name}» مرتبط بـ {$role->users()->count()} مستخدم.")
                ->warning()
                ->send();
            return;
        }

        $role->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if ($this->selectedRoleId === $roleId) {
            $this->selectedRoleId      = null;
            $this->selectedPermissions = [];
        }

        Notification::make()
            ->title('تم الحذف')
            ->body("تم حذف الدور «{$role->name}».")
            ->success()
            ->send();
    }

    /** اسم الدور المحدد للعرض */
    public function getSelectedRoleName(): ?string
    {
        if (! $this->selectedRoleId) return null;
        return Role::find($this->selectedRoleId)?->name;
    }
}
