<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManualEntryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'accounting.journal.view',
            'accounting.journal.create',
            'accounting.journal.reverse',
            'accounting.ledger.view',
            'accounting.trial_balance.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Super Admin — كل الصلاحيات صريحاً
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // محاسب التوزيع — إنشاء وعرض (بدون عكس)
        $distAccountant = Role::where('name', 'distribution_accountant')->first();
        if ($distAccountant) {
            $distAccountant->givePermissionTo([
                'accounting.journal.view',
                'accounting.journal.create',
                'accounting.ledger.view',
                'accounting.trial_balance.view',
            ]);
        }

        // مدير التوزيع — عرض فقط
        $distManager = Role::where('name', 'distribution_manager')->first();
        if ($distManager) {
            $distManager->givePermissionTo([
                'accounting.journal.view',
                'accounting.ledger.view',
                'accounting.trial_balance.view',
            ]);
        }

        // مدير المعرض — عرض القيود ودفتر الأستاذ فقط
        $showroomManager = Role::where('name', 'showroom_manager')->first();
        if ($showroomManager) {
            $showroomManager->givePermissionTo([
                'accounting.journal.view',
                'accounting.ledger.view',
            ]);
        }
    }
}
