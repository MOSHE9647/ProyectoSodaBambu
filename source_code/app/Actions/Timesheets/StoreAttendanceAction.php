<?php

namespace App\Actions\Timesheets;

use App\Models\Timesheet;
use Carbon\Carbon;

class StoreAttendanceAction
{
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