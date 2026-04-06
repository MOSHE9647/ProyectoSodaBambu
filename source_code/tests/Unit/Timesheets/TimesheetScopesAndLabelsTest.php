<?php

use App\Models\Timesheet;
use Carbon\Carbon;

/**
 * User Story: EIF-25_QA3 - Register employee clock-in and clock-out times including holidays.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-01_EIF-25_QA3 - for employee scope filters only selected employee records', function () {
    // Given: timesheets for two different employees.
    $target = Timesheet::factory()->create();
    Timesheet::factory()->create();

    // When: filtering by target employee id.
    $rows = Timesheet::query()->forEmployee($target->employee_id)->get();

    // Then: only rows from the selected employee are returned.
    expect($rows->every(fn (Timesheet $row) => $row->employee_id === $target->employee_id))->toBeTrue();
});

/**
 * User Story: EIF-25_QA3 - Register employee clock-in and clock-out times including holidays.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-02_EIF-25_QA3 - filter by date scope returns only records for a specific day', function () {
    // Given: timesheets across two different dates.
    Timesheet::factory()->create(['work_date' => '2026-03-10']);
    Timesheet::factory()->create(['work_date' => '2026-03-10']);
    Timesheet::factory()->create(['work_date' => '2026-03-11']);

    // When: filtering by an explicit date.
    $rows = Timesheet::query()->filterByDate('2026-03-10', null)->get();

    // Then: only records for that date are returned.
    expect($rows)->toHaveCount(2);
    expect($rows->every(fn (Timesheet $row) => Carbon::parse($row->work_date)->toDateString() === '2026-03-10'))->toBeTrue();
});

/**
 * User Story: EIF-25_QA3 - Register employee clock-in and clock-out times including holidays.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-03_EIF-25_QA3 - filter by month scope returns only records for selected month', function () {
    // Given: timesheets across two different months.
    Timesheet::factory()->create(['work_date' => '2026-03-10']);
    Timesheet::factory()->create(['work_date' => '2026-03-22']);
    Timesheet::factory()->create(['work_date' => '2026-04-01']);

    // When: filtering by month without explicit day.
    $rows = Timesheet::query()->filterByDate(null, '2026-03')->get();

    // Then: only records from March 2026 are returned.
    expect($rows)->toHaveCount(2);
    expect($rows->every(fn (Timesheet $row) => Carbon::parse($row->work_date)->format('Y-m') === '2026-03'))->toBeTrue();
});

/**
 * User Story: EIF-26_QA3 - Automatically calculate employee payroll with holiday double pay.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-02_EIF-26_QA3 - payroll half scope includes only first half days', function () {
    // Given: timesheets distributed in both halves of the month.
    Timesheet::factory()->create(['work_date' => '2026-03-10']);
    Timesheet::factory()->create(['work_date' => '2026-03-15']);
    Timesheet::factory()->create(['work_date' => '2026-03-16']);

    // When: applying first_half payroll scope.
    $rows = Timesheet::query()->forPayrollHalf('first_half')->get();

    // Then: only records from days 1 to 15 are returned.
    expect($rows)->toHaveCount(2);
    expect($rows->every(fn (Timesheet $row) => (int) Carbon::parse($row->work_date)->format('d') <= 15))->toBeTrue();
});

/**
 * User Story: EIF-26_QA3 - Automatically calculate employee payroll with holiday double pay.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-02_EIF-26_QA3 - payroll half scope includes only second half days', function () {
    // Given: timesheets distributed in both halves of the month.
    Timesheet::factory()->create(['work_date' => '2026-03-15']);
    Timesheet::factory()->create(['work_date' => '2026-03-16']);
    Timesheet::factory()->create(['work_date' => '2026-03-20']);

    // When: applying second_half payroll scope.
    $rows = Timesheet::query()->forPayrollHalf('second_half')->get();

    // Then: only records from day 16 onward are returned.
    expect($rows)->toHaveCount(2);
    expect($rows->every(fn (Timesheet $row) => (int) Carbon::parse($row->work_date)->format('d') >= 16))->toBeTrue();
});

/**
 * User Story: EIF-26_QA3 - Automatically calculate employee payroll with holiday double pay.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-04_EIF-26_QA3 - payroll period scope filters by month and year', function () {
    // Given: timesheets in two payroll periods.
    Timesheet::factory()->create(['work_date' => '2026-02-28']);
    Timesheet::factory()->create(['work_date' => '2026-03-05']);
    Timesheet::factory()->create(['work_date' => '2026-03-27']);

    // When: applying payroll period filter for March 2026.
    $rows = Timesheet::query()->forPayrollPeriod('2026-03')->get();

    // Then: only rows in March 2026 are returned.
    expect($rows)->toHaveCount(2);
    expect($rows->every(fn (Timesheet $row) => Carbon::parse($row->work_date)->format('Y-m') === '2026-03'))->toBeTrue();
});

/**
 * User Story: EIF-26_QA3 - Automatically calculate employee payroll with holiday double pay.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-04_EIF-26_QA3 - total hours labels and multipliers are formatted correctly for payroll rows', function () {
    // Given: one holiday row and one regular row with specific hours.
    $holiday = Timesheet::factory()->create([
        'total_hours' => 8.50,
        'is_holiday' => true,
    ]);

    $regular = Timesheet::factory()->create([
        'total_hours' => 7.00,
        'is_holiday' => false,
    ]);

    // Then: labels and multipliers match expected payroll display behavior.
    expect($holiday->total_hours_label)->toBe('8,5h');
    expect($regular->total_hours_label)->toBe('7h');
    expect($holiday->holiday_multiplier)->toBe(2);
    expect($regular->holiday_multiplier)->toBe(1);
});
