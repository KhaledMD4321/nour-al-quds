<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'data' => [
                    'name' => 'أحمد - مدير النظام',
                    'email' => 'admin@nour.test',
                    'password' => Hash::make('password'),
                    'business_unit_id' => null,
                    'is_active' => true,
                ],
                'role' => 'super_admin',
            ],
            [
                'data' => [
                    'name' => 'محمد - مدير المعرض',
                    'email' => 'showroom@nour.test',
                    'password' => Hash::make('password'),
                    'business_unit_id' => 1,
                    'is_active' => true,
                ],
                'role' => 'showroom_manager',
            ],
            [
                'data' => [
                    'name' => 'علي - مدير التوزيع',
                    'email' => 'dist@nour.test',
                    'password' => Hash::make('password'),
                    'business_unit_id' => 2,
                    'is_active' => true,
                ],
                'role' => 'distribution_manager',
            ],
        ];

        foreach ($users as $entry) {
            $user = User::updateOrCreate(
                ['email' => $entry['data']['email']],
                $entry['data']
            );

            // Sync a single role (removes all previous roles first)
            $user->syncRoles([$entry['role']]);
        }
    }
}
