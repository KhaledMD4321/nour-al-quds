<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ChequePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'finance.cheque.view',
            'finance.cheque.create',
            'finance.cheque.deposit',
            'finance.cheque.collect',
            'finance.cheque.bounce',
            'finance.cheque.replace',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Super Admin — كل الصلاحيات صريحاً
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // مدير التوزيع — كل الصلاحيات
        $distManager = Role::where('name', 'distribution_manager')->first();
        if ($distManager) {
            $distManager->givePermissionTo($permissions);
        }

        // محاسب التوزيع — كل شيء إلا الاستبدال
        $distAccountant = Role::where('name', 'distribution_accountant')->first();
        if ($distAccountant) {
            $distAccountant->givePermissionTo([
                'finance.cheque.view',
                'finance.cheque.create',
                'finance.cheque.deposit',
                'finance.cheque.collect',
                'finance.cheque.bounce',
            ]);
        }

        // مدير المعرض — عرض وإيداع فقط
        $showroomManager = Role::where('name', 'showroom_manager')->first();
        if ($showroomManager) {
            $showroomManager->givePermissionTo([
                'finance.cheque.view',
                'finance.cheque.deposit',
            ]);
        }
    }
}
