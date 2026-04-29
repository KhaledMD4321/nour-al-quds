<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TreasuryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'finance.treasury.view',
            'finance.treasury.create',
            'finance.treasury.edit',
            'finance.treasury.transfer',
            'finance.treasury.summary',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // السوبر أدمن: كل الصلاحيات
        Role::findByName('super_admin')->givePermissionTo($permissions);

        // مدير المعرض: عرض فقط
        Role::findByName('showroom_manager')->givePermissionTo([
            'finance.treasury.view',
        ]);

        // مدير التوزيع: عرض + تحويل
        Role::findByName('distribution_manager')->givePermissionTo([
            'finance.treasury.view',
            'finance.treasury.transfer',
        ]);

        // محاسب التوزيع: عرض + تحويل
        Role::findByName('distribution_accountant')->givePermissionTo([
            'finance.treasury.view',
            'finance.treasury.transfer',
        ]);
    }
}
