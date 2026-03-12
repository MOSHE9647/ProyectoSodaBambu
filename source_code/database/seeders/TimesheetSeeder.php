<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimesheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch some employees to associate with timesheets
        $employees = Employee::take(10)->get();

        // Create 50 timesheet records using the factory
        foreach ($employees as $employee) {
            Timesheet::factory()->count(5)->create([
                'employee_id' => $employee->id,
                'work_date' => now()->subDays(rand(1, 30)),
                'start_time' => now()->subHours(rand(1, 8)),
                'end_time' => now(),
                'total_hours' => rand(1, 8),
            ]);
        }
    }
}
