<?php

use App\Actions\Timesheets\CalculatePayrollSalaryAction;
use App\Enums\PaymentFrequency;
use App\Models\Employee;
use App\Models\Timesheet;

/**
 * Unit Story: EIF-26 - Payroll calculation with holiday multipliers.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-01_EIF-26 - calculates base salary without holiday multiplier', function () {
    // Given: an employee with hourly wage of ₡5,000 and 40 hours worked (no holidays).
    $employee = Employee::factory()->create(['hourly_wage' => 5000]);
    $timesheets = Timesheet::factory()
        ->count(5)
        ->create([
            'employee_id' => $employee->id,
            'total_hours' => 9,
            'is_holiday' => false,
        ]);

    // When: calculating payroll for the period.
    $action = new CalculatePayrollSalaryAction;
    $result = $action->execute($employee, $timesheets);

    // Then: total salary equals 45 hours × ₡5,000 = ₡225,000.
    expect($result['total_salary_amount_cents'])->toBe(22500000)
        ->and($result['total_salary_amount_label'])->toContain('225 000,00');
});

test('CP-02_EIF-26 - applies 2x salary multiplier for holiday hours', function () {
    // Given: an employee with hourly wage of ₡5,000 with 9 holiday hours and 36 regular hours.
    $employee = Employee::factory()->create(['hourly_wage' => 5000]);
    $timesheets = collect([
        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'total_hours' => 9,
            'is_holiday' => true,
        ]),
        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'total_hours' => 9,
            'is_holiday' => false,
        ]),
        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'total_hours' => 9,
            'is_holiday' => false,
        ]),
        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'total_hours' => 9,
            'is_holiday' => false,
        ]),
    ]);

    // When: calculating payroll with holiday multiplier.
    $action = new CalculatePayrollSalaryAction;
    $result = $action->execute($employee, $timesheets);

    // Then: total salary = (9 × ₡5,000 × 2) + (27 × ₡5,000) = ₡225,000.
    expect($result['total_salary_amount_cents'])->toBe(22500000)
        ->and($result['includes_holiday_days'])->toBeTrue();
});

test('CP-04_EIF-26 - supports biweekly payment frequency enum', function () {
    // Given: an employee with biweekly payment frequency.
    $employee = Employee::factory()->create([
        'hourly_wage' => 5000,
        'payment_frequency' => PaymentFrequency::BIWEEKLY,
    ]);

    // When: accessing payment frequency.
    // Then: returns BIWEEKLY enum.
    expect($employee->payment_frequency)->toBe(PaymentFrequency::BIWEEKLY);
});

test('CP-06_EIF-26 - formats currency with Costa Rican convention', function () {
    // Given: a calculated salary of ₡200,000.
    $employee = Employee::factory()->create(['hourly_wage' => 5000]);
    $timesheets = Timesheet::factory()
        ->count(5)
        ->create([
            'employee_id' => $employee->id,
            'total_hours' => 9,
            'is_holiday' => false,
        ]);

    // When: calculating payroll.
    $action = new CalculatePayrollSalaryAction;
    $result = $action->execute($employee, $timesheets);

    // Then: formatted with ₡ symbol, thousands separator, and two decimals.
    expect($result['total_salary_amount_label'])->toMatch('/^₡[\d ]+,\d{2}$/');
});
