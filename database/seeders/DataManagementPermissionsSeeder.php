<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DataManagementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'data.export',
            'data.import',
            'data.period_manager',
            'data.cleanup',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $this->command->info('Data Management permissions seeded (' . count($permissions) . ')');
    }
}
