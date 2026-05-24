<?php

namespace Database\Seeders;

use App\Models\FiscalPeriod;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FiscalPeriodSeeder extends Seeder
{
    public function run(): void
    {
        // Seed 2025 and 2026
        foreach ([2025, 2026] as $year) {
            for ($month = 1; $month <= 12; $month++) {
                $start = Carbon::create($year, $month, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();

                FiscalPeriod::updateOrCreate(
                    ['year' => $year, 'month' => $month],
                    [
                        'start_date' => $start->toDateString(),
                        'end_date' => $end->toDateString(),
                        'is_locked' => false,
                        'locked_by' => null,
                        'locked_at' => null,
                    ]
                );
            }
        }
    }
}
