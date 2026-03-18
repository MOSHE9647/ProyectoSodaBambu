<?php

namespace App\Actions\Timesheets;

use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Support\Collection;

class CalculatePayrollSalaryAction
{
    /**
     * Build salary totals and daily breakdown with cent-level precision.
     *
     * @param Collection<int, Timesheet> $timesheets
     * @return array<string, mixed>
     */
    public function execute(Employee $employee, Collection $timesheets): array
    {
        $hourlyWageCents = $this->toCents($employee->hourly_wage_raw);

        $timesheetRows = $timesheets->map(function (Timesheet $timesheet) use ($hourlyWageCents): array {
            $hours = $timesheet->total_hours_raw;
            $multiplier = $timesheet->holiday_multiplier;
            $dailySalaryCents = $this->calculateDailySalaryCents($hourlyWageCents, $hours, $multiplier);

            return [
                'id' => $timesheet->id,
                'work_date_label' => $timesheet->work_date_label,
                'start_time_label' => $timesheet->start_time_label,
                'end_time_label' => $timesheet->end_time_label,
                'total_hours' => $hours,
                'total_hours_label' => $timesheet->total_hours_label,
                'is_holiday' => $timesheet->is_holiday,
                'salary_amount' => $dailySalaryCents / 100,
                'salary_amount_cents' => $dailySalaryCents,
                'salary_amount_label' => $this->formatCurrencyFromCents($dailySalaryCents),
            ];
        })->values();

        $totalWorkedHours = $this->sumWorkedHours($timesheetRows);
        $totalSalaryCents = (int) $timesheetRows->sum('salary_amount_cents');
        $regularHours = $this->sumHoursByHolidayType($timesheetRows, false);
        $holidayHours = $this->sumHoursByHolidayType($timesheetRows, true);

        return [
            'timesheets' => $timesheetRows,
            'worked_days' => $timesheetRows->where('total_hours', '>', 0)->count(),
            'regular_hours' => $regularHours,
            'holiday_hours' => $holidayHours,
            'total_worked_hours' => $totalWorkedHours,
            'total_worked_hours_label' => $this->formatHours($totalWorkedHours),
            'total_salary_amount' => $totalSalaryCents / 100,
            'total_salary_amount_cents' => $totalSalaryCents,
            'total_salary_amount_label' => $this->formatCurrencyFromCents($totalSalaryCents),
            'includes_holiday_days' => $timesheetRows->contains(fn (array $row) => $row['is_holiday']),
        ];
    }

    /**
     * Calculate day salary from hourly wage, worked hours and holiday multiplier.
     */
    private function calculateDailySalaryCents(int $hourlyWageCents, float $hours, int $multiplier): int
    {
        $hoursHundredths = (int) round($hours * 100);
        return (int) round(($hourlyWageCents * $hoursHundredths * $multiplier) / 100);
    }

    /**
     * Sum worked hours in the payroll period.
     *
     * @param Collection<int, array<string, mixed>> $timesheetRows
     */
    private function sumWorkedHours(Collection $timesheetRows): float
    {
        return (float) $timesheetRows->sum(fn (array $row) => (float) $row['total_hours']);
    }

    /**
     * Sum worked hours separated by holiday type.
     *
     * @param Collection<int, array<string, mixed>> $timesheetRows
     */
    private function sumHoursByHolidayType(Collection $timesheetRows, bool $isHoliday): float
    {
        return (float) $timesheetRows
            ->where('is_holiday', $isHoliday)
            ->sum(fn (array $row) => (float) $row['total_hours']);
    }

    /**
     * Convert decimal amount to cents.
     */
    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Format amount in CRC with 2 decimals.
     */
    public function formatCurrencyFromCents(int $cents): string
    {
        return '₡' . number_format($cents / 100, 2, ',', ' ');
    }

    /**
     * Format worked hours preserving 2 decimals only when required.
     */
    private function formatHours(float $hours): string
    {
        $normalized = rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.');
        return str_replace('.', ',', $normalized) . 'h';
    }
}