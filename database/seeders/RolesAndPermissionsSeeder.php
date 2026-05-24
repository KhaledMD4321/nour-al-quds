<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. Permissions ────────────────────────────────────────────────────

        $permissions = [
            'sales.quick.create',
            'sales.invoice.create',
            'sales.invoice.delete',
            'inventory.view',
            'inventory.transfer',
            'inventory.adjust',
            'finance.treasury.view',
            'finance.receipt.create',
            'finance.payment.create',
            'reports.sales',
            'reports.profit_loss',
            'accounting.journal',
            'accounting.lock_period',
            'settings.users',
            'settings.company',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── 2. Roles & Permission Assignments ─────────────────────────────────

        // super_admin — كل الصلاحيات
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // showroom_manager — مدير المعرض
        $showroomManager = Role::firstOrCreate(['name' => 'showroom_manager', 'guard_name' => 'web']);
        $showroomManager->syncPermissions([
            'sales.quick.create',
            'sales.invoice.create',
            'inventory.view',
            'inventory.transfer',
            'finance.treasury.view',
            'finance.receipt.create',
            'reports.sales',
        ]);

        // showroom_cashier — كاشير المعرض
        $showroomCashier = Role::firstOrCreate(['name' => 'showroom_cashier', 'guard_name' => 'web']);
        $showroomCashier->syncPermissions([
            'sales.quick.create',
            'sales.invoice.create',
        ]);

        // distribution_manager — مدير التوزيع
        $distributionManager = Role::firstOrCreate(['name' => 'distribution_manager', 'guard_name' => 'web']);
        $distributionManager->syncPermissions([
            'sales.invoice.create',
            'inventory.view',
            'inventory.transfer',
            'inventory.adjust',
            'finance.treasury.view',
            'finance.receipt.create',
            'finance.payment.create',
            'reports.sales',
        ]);

        // distribution_accountant — محاسب التوزيع
        $distributionAccountant = Role::firstOrCreate(['name' => 'distribution_accountant', 'guard_name' => 'web']);
        $distributionAccountant->syncPermissions([
            'finance.treasury.view',
            'finance.receipt.create',
            'finance.payment.create',
            'accounting.journal',
        ]);

        // warehouse_keeper — أمين المخزن
        $warehouseKeeper = Role::firstOrCreate(['name' => 'warehouse_keeper', 'guard_name' => 'web']);
        $warehouseKeeper->syncPermissions([
            'inventory.view',
            'inventory.transfer',
            'inventory.adjust',
        ]);
    }
}
