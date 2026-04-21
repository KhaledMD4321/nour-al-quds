<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySettingsSeeder::class,
            BusinessUnitSeeder::class,
            ChartOfAccountsSeeder::class,
            FiscalPeriodSeeder::class,
            TaxRateSeeder::class,
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            CompanySeeder::class,
            CategorySeeder::class,
        ]);
    }
}
