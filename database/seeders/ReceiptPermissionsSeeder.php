<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ReceiptPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_any_receipt',
            'view_receipt',
            'create_receipt',
            'delete_receipt',  // soft-delete (أرشفة)
            'print_receipt',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Super Admin يرث كل الصلاحيات تلقائياً عبر Gate::before

        // مدير الفرع (unit_manager) — كل صلاحيات الإيصالات
        $manager = Role::where('name', 'unit_manager')->first();
        if ($manager) {
            $manager->givePermissionTo($permissions);
        }

        // محاسب — كل الصلاحيات إلا الحذف
        $accountant = Role::where('name', 'accountant')->first();
        if ($accountant) {
            $accountant->givePermissionTo([
                'view_any_receipt',
                'view_receipt',
                'create_receipt',
                'print_receipt',
            ]);
        }

        // كاشير — إنشاء وعرض وطباعة
        $cashier = Role::where('name', 'cashier')->first();
        if ($cashier) {
            $cashier->givePermissionTo([
                'view_any_receipt',
                'view_receipt',
                'create_receipt',
                'print_receipt',
            ]);
        }
    }
}
