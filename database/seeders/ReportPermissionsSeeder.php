<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ReportPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'reports.aging.view',
            'reports.pl.view',
            'reports.inventory.view',
            'reports.sales.view',
            'reports.purchases.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $this->command->info('Report permissions seeded ('.count($permissions).')');
    }
}
