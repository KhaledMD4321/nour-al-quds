<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PaymentPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'finance.payment.view',
            'finance.payment.create',
            'finance.payment.print',
            'finance.payment.delete',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Super Admin — كل الصلاحيات صريحاً
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // مدير المعرض
        $showroomManager = Role::where('name', 'showroom_manager')->first();
        if ($showroomManager) {
            $showroomManager->givePermissionTo([
                'finance.payment.view',
                'finance.payment.create',
                'finance.payment.print',
            ]);
        }

        // مدير التوزيع
        $distManager = Role::where('name', 'distribution_manager')->first();
        if ($distManager) {
            $distManager->givePermissionTo([
                'finance.payment.view',
                'finance.payment.create',
                'finance.payment.print',
            ]);
        }

        // محاسب التوزيع
        $distAccountant = Role::where('name', 'distribution_accountant')->first();
        if ($distAccountant) {
            $distAccountant->givePermissionTo([
                'finance.payment.view',
                'finance.payment.create',
                'finance.payment.print',
            ]);
        }
    }
}
