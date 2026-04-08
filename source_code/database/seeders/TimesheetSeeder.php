<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Timesheet;
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
        $usedDates = [];

        // Create 50 timesheet records using the factory
        foreach ($employees as $employee) {
            for ($i = 0; $i < 3; $i++) {
                // Generate unique work date
                do {
                    $workDate = now()->subDays(rand(1, 30));
                    $dateKey = $workDate->format('Y-m-d');
                } while (in_array($dateKey, $usedDates));

                $usedDates[] = $dateKey;

                // Generate random start time between 7 AM and 7 PM
                $startHour = rand(7, 19);
                $startTime = $workDate->clone()->setHour($startHour)->setMinute(0)->setSecond(0);

                // Generate random end time after start time, between start hour and 7 PM
                $endHour = rand($startHour + 1, 19);
                $endTime = $workDate->clone()->setHour($endHour)->setMinute(0)->setSecond(0);

                // Calculate total hours worked
                $totalHours = $startTime->diffInHours($endTime);

                Timesheet::factory()->create([
                    'employee_id' => $employee->id,
                    'work_date' => $workDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'total_hours' => $totalHours,
                ]);
            }
        }
    }
}
