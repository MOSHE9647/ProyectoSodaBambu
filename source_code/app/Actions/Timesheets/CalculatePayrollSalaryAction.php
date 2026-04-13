<?php

namespace App\Actions\Timesheets;

use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Support\Collection;

class CalculatePayrollSalaryAction
{
    /**
     * Calculate payroll salary totals and daily breakdown with cent-level precision.
     *
     * Processes all timesheets for an employee and builds a comprehensive salary report including:
     * - Individual daily salary calculations with holiday multiplier adjustments
     * - Aggregated totals for worked hours, regular hours, holiday hours, and salary
     * - Formatted labels for display in UI (currency and time formats)
     *
     * @param  Employee  $employee  The employee for whom payroll is being calculated
     * @param  Collection<int, Timesheet>  $timesheets  Collection of timesheets to process
     * @return array<string, mixed> Associative array containing:
     *                              - timesheets: Processed timesheet rows with salary calculations
     *                              - worked_days: Count of days with work hours > 0
     *                              - regular_hours: Total hours worked on non-holiday days
     *                              - holiday_hours: Total hours worked on holiday days
     *                              - total_worked_hours: Combined regular and holiday hours
     *                              - total_worked_hours_label: Formatted hours display string
     *                              - total_salary_amount: Final salary in decimal format (CRC)
     *                              - total_salary_amount_cents: Final salary in cents (integer)
     *                              - total_salary_amount_label: Formatted currency display string
     *                              - includes_holiday_days: Boolean indicating if period includes holidays
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
     * Calculate daily salary in cents from hourly wage, worked hours, and holiday multiplier.
     *
     * Uses cent-level precision arithmetic to avoid floating-point rounding errors.
     * Converts hours to hundredths for integer multiplication, then applies the holiday multiplier
     * to calculate the final gross salary for the day.
     *
     * Formula: (hourlyWageCents × hoursHundredths × multiplier) / 100
     * where hourly wage is in cents and hours are multiplied by 100 (e.g., 8.5 hours = 850)
     *
     * @param  int  $hourlyWageCents  Employee's hourly wage in cents (e.g., 1000 = ₡10.00)
     * @param  float  $hours  Total hours worked in the day (e.g., 8.5)
     * @param  int  $multiplier  Holiday multiplier (1 = regular, 2 = double pay, etc.)
     * @return int Daily salary amount in cents
     */
    private function calculateDailySalaryCents(int $hourlyWageCents, float $hours, int $multiplier): int
    {
        $hoursHundredths = (int) round($hours * 100);

        return (int) round(($hourlyWageCents * $hoursHundredths * $multiplier) / 100);
    }

    /**
     * Sum all worked hours across the payroll period.
     *
     * Iterates through timesheet rows and accumulates total hours worked,
     * regardless of whether they are regular or holiday hours.
     *
     * @param  Collection<int, array<string, mixed>>  $timesheetRows  Processed timesheet data
     * @return float Total hours worked in the period
     */
    private function sumWorkedHours(Collection $timesheetRows): float
    {
        return (float) $timesheetRows->sum(fn (array $row) => (float) $row['total_hours']);
    }

    /**
     * Sum worked hours filtered by holiday status.
     *
     * Separates worked hours into two categories: regular days (is_holiday=false)
     * and holiday days (is_holiday=true). Used to track overtime or special
     * compensation requirements based on day type.
     *
     * @param  Collection<int, array<string, mixed>>  $timesheetRows  Processed timesheet data
     * @param  bool  $isHoliday  Filter flag: true for holidays, false for regular days
     * @return float Total hours for the specified day type
     */
    private function sumHoursByHolidayType(Collection $timesheetRows, bool $isHoliday): float
    {
        return (float) $timesheetRows
            ->where('is_holiday', $isHoliday)
            ->sum(fn (array $row) => (float) $row['total_hours']);
    }

    /**
     * Convert a decimal monetary amount to cents (integer).
     *
     * Multiplies by 100 and rounds to the nearest integer to handle floating-point
     * precision issues. Essential for maintaining cent-level accuracy throughout
     * all salary calculations without accumulating rounding errors.
     *
     * Example: 10.5 → 1050 (₡10.50 in cents)
     *
     * @param  float  $amount  Decimal amount in currency units (e.g., 10.50 for ₡10.50)
     * @return int Amount in cents (e.g., 1050 for ₡10.50)
     */
    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Format an amount in cents as a Costa Rican currency display string.
     *
     * Converts cent-based amounts back to decimal representation and formats with:
     * - Currency symbol (₡)
     * - Exactly 2 decimal places
     * - Space as thousands separator
     * - Comma as decimal separator (following Costa Rican locale conventions)
     *
     * Example: 1050 cents → "₡10,50"
     *
     * @param  int  $cents  Amount in cents (e.g., 1050 for ₡10.50)
     * @return string Formatted currency display (e.g., "₡10,50" or "₡1 234,56")
     */
    public function formatCurrencyFromCents(int $cents): string
    {
        return '₡'.number_format($cents / 100, 2, ',', ' ');
    }

    /**
     * Format worked hours for display with intelligent decimal handling.
     *
     * Formats hours to 2 decimal places, then removes trailing zeros and decimal point
     * to show the simplest representation. Uses comma as decimal separator following
     * Costa Rican locale conventions, and appends 'h' suffix.
     *
     * Examples:
     * - 8.5 hours → "8,5h"
     * - 8.0 hours → "8h"
     * - 24.25 hours → "24,25h"
     *
     * @param  float  $hours  Decimal hours to format
     * @return string Formatted hours display with 'h' suffix
     */
    private function formatHours(float $hours): string
    {
        $normalized = rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.');

        return str_replace('.', ',', $normalized).'h';
    }
}
