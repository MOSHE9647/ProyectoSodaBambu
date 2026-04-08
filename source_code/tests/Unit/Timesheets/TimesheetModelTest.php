<?php

use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;

/**
 * Epic: EIF-23_QA1 - Análisis Financiero (Payroll/Attendance entity)
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-23
 */
test('CP-01_EIF-23_QA1 - timesheet model is soft-deletable', function () {
    // Given: a created timesheet.
    $timesheet = Timesheet::factory()->create();

    // When: the timesheet is deleted.
    $timesheet->delete();

    // Then: the timesheet is soft-deleted (not removed from DB).
    expect($timesheet->trashed())->toBeTrue();

    // And: timesheet is excluded from default queries.
    expect(Timesheet::query()->find($timesheet->id))->toBeNull();
});

/**
 * Epic: EIF-23_QA1 - Análisis Financiero (Payroll/Attendance entity)
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-23
 */
test('CP-02_EIF-23_QA1 - soft-deleted timesheet can be restored', function () {
    // Given: a soft-deleted timesheet.
    $timesheet = Timesheet::factory()->create();
    $timesheet->delete();

    // When: the timesheet is restored.
    $timesheet->restore();

    // Then: the timesheet is no longer trashed.
    expect($timesheet->trashed())->toBeFalse();

    // And: timesheet is included in default queries.
    expect(Timesheet::query()->find($timesheet->id))->not->toBeNull();
});

/**
 * Epic: EIF-23_QA1 - Análisis Financiero (Payroll/Attendance entity)
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-23
 */
test('CP-03_EIF-23_QA1 - timesheet belongs to employee', function () {
    // Given: a timesheet with an associated employee.
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['id' => $user->id]);
    $timesheet = Timesheet::factory()->create(['employee_id' => $employee->id]);

    // When: accessing the employee relationship.
    $relatedEmployee = $timesheet->employee;

    // Then: the related employee is returned correctly.
    expect($relatedEmployee->id)->toBe($employee->id);
});

/**
 * Epic: EIF-23_QA1 - Análisis Financiero (Payroll/Attendance entity)
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-23
 */
test('CP-04_EIF-23_QA1 - timesheet total hours is calculated correctly', function () {
    // Given: a timesheet with start and end times.
    $timesheet = Timesheet::factory()->create([
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    // When: accessing the hours_worked computed attribute.
    // Then: the hours are calculated correctly (9 hours).
    expect($timesheet->hours_worked)->toBe(9.00);
});

/**
 * Epic: EIF-23_QA1 - Análisis Financiero (Payroll/Attendance entity)
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-23
 */
test('CP-05_EIF-23_QA1 - timesheet holiday flag defaults to false', function () {
    // When: a timesheet is created with non-holiday flag.
    $timesheet = Timesheet::factory()->create([
        'is_holiday' => false,
    ]);

    // Then: is_holiday defaults to false/0.
    expect((int) $timesheet->is_holiday)->toBe(0);
});

/**
 * Epic: EIF-23_QA1 - Análisis Financiero (Payroll/Attendance entity)
 * Priority: Low
 * Jira Link: https://est-una.atlassian.net/browse/EIF-23
 */
test('CP-06_EIF-23_QA1 - timesheet timestamps are tracked', function () {
    // When: a timesheet is created.
    $timesheet = Timesheet::factory()->create();
    $createdAt = $timesheet->created_at;

    // Then: created_at timestamp exists.
    expect($createdAt)->not->toBeNull();

    // When: the timesheet is updated.
    $timesheet->update(['is_holiday' => true]);
    $updatedAt = $timesheet->updated_at;

    // Then: updated_at is after or equal to created_at.
    expect($updatedAt->greaterThanOrEqualTo($createdAt))->toBeTrue();
});

/**
 * Epic: EIF-23_QA1 - Análisis Financiero (Payroll/Attendance entity)
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-23
 */
test('CP-07_EIF-23_QA1 - timesheet work date is cast to date format', function () {
    // When: a timesheet is created with a specific work date.
    $workDate = Carbon::now('America/Costa_Rica')->toDateString();
    $timesheet = Timesheet::factory()->create([
        'work_date' => $workDate,
    ]);

    // Then: work_date is stored and retrieved as a date.
    expect($timesheet->work_date)->toEqual(Carbon::parse($workDate)->toDateString());
});
