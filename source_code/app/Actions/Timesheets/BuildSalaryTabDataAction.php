<?php

namespace App\Actions\Timesheets;

use App\Enums\PaymentFrequency;
use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Orchestrates salary tab data for a selected employee.
 * Aggregates employee information, timesheets, and salary calculations for a complete payroll overview.
 * Supports both monthly and biweekly payment frequencies with automatic date range adjustments.
 */
class BuildSalaryTabDataAction
{
    private const TZ = 'America/Costa_Rica';

    /**
     * @param  CalculatePayrollSalaryAction  $calculatePayrollSalaryAction  Service for computing salary summaries.
     */
    public function __construct(
        private readonly CalculatePayrollSalaryAction $calculatePayrollSalaryAction,
    ) {}

    /**
     * Build salary tab payload with normalized values for selected employee.
     *
     * Retrieves employees, resolves selected employee, determines payroll period and half,
     * fetches timesheets, and builds comprehensive employee payload with salary calculations.
     *
     * @param  int|null  $employeeId  Employee ID (defaults to first employee if not found)
     * @param  string|null  $payrollPeriod  Period in 'YYYY-MM' format (defaults to current month)
     * @param  string|null  $payrollHalf  'first_half' or 'second_half' for biweekly employees only
     * @return array{employees: Collection<int, Employee>, employee: array<string, mixed>|null}
     *                                                                                          - employees: Sorted collection of all employees
     *                                                                                          - employee: Payload with id, period info, employee data, and salary summaries (null if no employees)
     */
    public function execute(?int $employeeId, ?string $payrollPeriod, ?string $payrollHalf): array
    {
        $employees = Employee::query()
            ->with(['user'])
            ->get()
            ->sortBy(fn (Employee $employee) => $employee->display_name, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if ($employees->isEmpty()) {
            return [
                'employees' => $employees,
                'employee' => null,
            ];
        }

        $selectedEmployee = $this->resolveSelectedEmployee($employees, $employeeId);
        $selectedPeriod = $this->resolvePayrollPeriod($payrollPeriod);
        $isBiweekly = $selectedEmployee->payment_frequency === PaymentFrequency::BIWEEKLY;
        $selectedHalf = $isBiweekly
            ? $this->resolvePayrollHalf($payrollHalf)
            : null;

        [$periodStartDate, $periodEndDate, $periodWindowDays] = $this->resolvePayrollDateRange(
            $selectedEmployee,
            $selectedPeriod,
            $selectedHalf,
        );

        $timesheets = Timesheet::query()
            ->forEmployee($selectedEmployee->id)
            ->forWorkDateRange($periodStartDate, $periodEndDate)
            ->orderForPayroll()
            ->get();

        $payload = $this->buildEmployeePayload(
            employee: $selectedEmployee,
            timesheets: $timesheets,
            selectedPeriod: $selectedPeriod,
            selectedHalf: $selectedHalf,
            periodStartDate: $periodStartDate,
            periodEndDate: $periodEndDate,
            periodWindowDays: $periodWindowDays,
        );

        return [
            'employees' => $employees,
            'employee' => $payload,
        ];
    }

    /**
     * Resolve employee for salary rendering from ID or default to first.
     *
     * @param  Collection<int, Employee>  $employees  Sorted collection of employees
     * @param  int|null  $employeeId  Employee ID to locate
     * @return Employee Matching employee by ID or first in collection
     */
    private function resolveSelectedEmployee(Collection $employees, ?int $employeeId): Employee
    {
        return $employees->firstWhere('id', $employeeId)
            ?? $employees->first();
    }

    /**
     * Resolve payroll period with validation (YYYY-MM format).
     * Falls back to current month in Costa Rica timezone if invalid.
     *
     * @param  string|null  $payrollPeriod  Period in 'YYYY-MM' format
     * @return string Valid period in 'YYYY-MM' format
     */
    private function resolvePayrollPeriod(?string $payrollPeriod): string
    {
        if ($payrollPeriod && preg_match('/^\d{4}-\d{2}$/', $payrollPeriod)) {
            return $payrollPeriod;
        }

        return Carbon::now(self::TZ)->format('Y-m');
    }

    /**
     * Resolve payroll half ('first_half' or 'second_half') for biweekly employees.
     * Falls back to current half based on day of month (1-15 = first, 16+ = second).
     *
     * @param  string|null  $payrollHalf  Half identifier
     * @return string Valid half: 'first_half' or 'second_half'
     */
    private function resolvePayrollHalf(?string $payrollHalf): string
    {
        if (in_array($payrollHalf, ['first_half', 'second_half'], true)) {
            return $payrollHalf;
        }

        return Carbon::now(self::TZ)->day <= 15 ? 'first_half' : 'second_half';
    }

    /**
     * Resolve inclusive payroll date range (15 or 30 days) based on payment frequency.
     * Monthly: 1st to last day. Biweekly: 15-day windows (1-15, 16-31 or prev 16 to curr 15).
     *
     * @param  Employee  $employee  Employee with payment_frequency property
     * @param  string  $selectedPeriod  Period in 'YYYY-MM' format
     * @param  string|null  $selectedHalf  'first_half' or 'second_half' for biweekly only
     * @return array{string, string, int} [periodBegin, periodEnd, windowDays] as YYYY-MM-DD dates
     */
    private function resolvePayrollDateRange(Employee $employee, string $selectedPeriod, ?string $selectedHalf): array
    {
        [$year, $month] = array_map('intval', explode('-', $selectedPeriod));
        $periodStart = Carbon::create($year, $month, 1, 0, 0, 0, self::TZ);

        $isBiweekly = $employee->payment_frequency === PaymentFrequency::BIWEEKLY;
        $windowDays = $isBiweekly ? 15 : 30;

        $periodEnd = match (true) {
            ! $isBiweekly => $periodStart->copy()->endOfMonth(),
            $selectedHalf === 'first_half' => $periodStart->copy()->day(15),
            default => $periodStart->copy()->endOfMonth(),
        };

        $periodBegin = $periodEnd->copy()->subDays($windowDays - 1);

        return [$periodBegin->toDateString(), $periodEnd->toDateString(), $windowDays];
    }

    /**
     * Build normalized employee payload for salary tab rendering.
     * Combines employee data, period info, and salary calculations from CalculatePayrollSalaryAction.
     *
     * @param  Employee  $employee  Employee with personal, wage, and frequency data
     * @param  Collection<int, Timesheet>  $timesheets  Timesheets for the payroll period
     * @param  string  $selectedPeriod  Period in 'YYYY-MM' format
     * @param  string|null  $selectedHalf  'first_half' or 'second_half' for biweekly only
     * @param  string  $periodStartDate  Period start as YYYY-MM-DD
     * @param  string  $periodEndDate  Period end as YYYY-MM-DD
     * @param  int  $periodWindowDays  Days in period (15 or 30)
     * @return array<string, mixed> Payload with id, period info, employee data, and salary summaries
     */
    private function buildEmployeePayload(
        Employee $employee,
        Collection $timesheets,
        string $selectedPeriod,
        ?string $selectedHalf,
        string $periodStartDate,
        string $periodEndDate,
        int $periodWindowDays,
    ): array {
        $salarySummary = $this->calculatePayrollSalaryAction->execute($employee, $timesheets);

        return [
            'id' => $employee->id,
            'selected_employee_id' => $employee->id,
            'payroll_period' => $selectedPeriod,
            'payroll_half' => $selectedHalf,
            'is_biweekly' => $employee->payment_frequency === PaymentFrequency::BIWEEKLY,
            'period_start_date' => $periodStartDate,
            'period_end_date' => $periodEndDate,
            'period_window_days' => $periodWindowDays,
            'name' => $employee->display_name,
            'email' => $employee->display_email,
            'initials' => $employee->initials,
            'status_label' => $employee->status_label,
            'is_active' => $employee->is_active,
            'hourly_wage_raw' => $employee->hourly_wage_raw,
            'hourly_wage_label' => $employee->hourly_wage_label,
            'payment_frequency' => $employee->payment_frequency?->value,
            'payment_frequency_label' => $employee->payment_frequency_label,
            ...$salarySummary,
        ];
    }
}
