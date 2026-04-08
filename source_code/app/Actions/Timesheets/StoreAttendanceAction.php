<?php

namespace App\Actions\Timesheets;

use App\Models\Timesheet;
use Carbon\Carbon;

class StoreAttendanceAction
{
    /**
     * Store or update an employee attendance record (timesheet entry).
     *
     * Creates a new timesheet or updates an existing one with work date, time in/out,
     * and holiday status. Automatically calculates total hours worked based on start
     * and end times. Uses updateOrCreate when no specific timesheet is provided,
     * preventing duplicate entries for the same employee on the same date.
     *
     * @param  array<string, mixed>  $data  Input data containing:
     *                                      - employee_id: (int|string) Employee identifier
     *                                      - work_date: (string) Work date in format accepted by Carbon (e.g., '2026-03-18')
     *                                      - start_time: (string) Clock-in time (e.g., '08:00', '08:00:00')
     *                                      - end_time: (string|null) Clock-out time (null for incomplete shifts)
     *                                      - is_holiday: (bool|int) Optional flag, defaults to false
     * @param  Timesheet|null  $timesheet  Existing timesheet to update. If null, creates new or updates via upsert
     * @return Timesheet The stored or updated timesheet with all current data
     */
    public function execute(array $data, ?Timesheet $timesheet = null): Timesheet
    {
        $payload = [
            'employee_id' => (int) $data['employee_id'],
            'work_date' => $data['work_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'is_holiday' => (bool) ($data['is_holiday'] ?? false),
        ];

        $payload['total_hours'] = $this->calculateTotalHours(
            $payload['work_date'],
            $payload['start_time'],
            $payload['end_time'],
        );

        if ($timesheet) {
            $timesheet->fill($payload);
            $timesheet->save();

            return $timesheet->refresh();
        }

        return Timesheet::updateOrCreate(
            [
                'employee_id' => $payload['employee_id'],
                'work_date' => $payload['work_date'],
            ],
            $payload,
        );
    }

    /**
     * Calculate total work hours from start and end times on a specific date.
     *
     * Parses clock-in and clock-out times on the given work date and computes the duration
     * in hours. Handles edge cases: returns 0 if end time is null, missing, or if end time
     * is not after start time (e.g., same time or previous time). Results are rounded to
     * 2 decimal places (e.g., 8.5 hours, 7.75 hours).
     *
     * @param  string  $workDate  The work date (e.g., '2026-03-18')
     * @param  string  $startTime  Clock-in time in any Carbon-parseable format (e.g., '08:30', '08:30:00')
     * @param  string|null  $endTime  Clock-out time, or null if shift is incomplete
     * @return float Total hours worked, rounded to 2 decimal places. Returns 0 if:
     *               - endTime is null or empty
     *               - endTime is not after startTime
     */
    private function calculateTotalHours(string $workDate, string $startTime, ?string $endTime): float
    {
        if (blank($endTime)) {
            return 0;
        }

        $start = Carbon::parse("{$workDate} {$startTime}");
        $end = Carbon::parse("{$workDate} {$endTime}");

        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }
}
