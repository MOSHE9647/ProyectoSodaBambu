<?php

namespace App\Actions\Timesheets;

use App\Enums\PaymentFrequency;
use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildSalaryTabDataAction
{
    private const TZ = 'America/Costa_Rica';

    public function __construct(
        private readonly CalculatePayrollSalaryAction $calculatePayrollSalaryAction,
    ) {
    }

    /**
     * Build the salary tab payload with normalized values for the selected employee.
     *
     * @return array{employees: Collection<int, Employee>, employee: array<string, mixed>|null}
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
     * Resolve the employee for salary rendering.
     */
    private function resolveSelectedEmployee(Collection $employees, ?int $employeeId): Employee
    {
        return $employees->firstWhere('id', $employeeId)
            ?? $employees->first();
    }

    /**
     * Resolve payroll period from input or fallback to current month.
     */
    private function resolvePayrollPeriod(?string $payrollPeriod): string
    {
        if ($payrollPeriod && preg_match('/^\d{4}-\d{2}$/', $payrollPeriod)) {
            return $payrollPeriod;
        }

        return Carbon::now(self::TZ)->format('Y-m');
    }

    /**
     * Resolve payroll half from input or fallback to current half.
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
     *
     * @return array{string, string, int}
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
     * Build a normalized employee payload for salary tab rendering.
     *
     * @param Collection<int, Timesheet> $timesheets
     * @return array<string, mixed>
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
