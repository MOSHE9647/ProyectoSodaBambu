<?php

use App\Models\Timesheet;

/**
 * Unit Story: EIF-25 - Attendance registration validation with holidays.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-01_EIF-25 - calculates hours worked correctly from start and end times', function () {
    // Given: a timesheet entry with start time of 08:00 and end time of 17:00.
    $timesheet = Timesheet::make([
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    // When: accessing the hours_worked attribute.
    // Then: returns 9 hours.
    expect($timesheet->hours_worked)->toBe(9.0);
});

test('CP-02_EIF-25 - calculates partial hours correctly', function () {
    // Given: a timesheet with fractional hour values (08:30 to 12:45).
    $timesheet = Timesheet::make([
        'start_time' => '08:30',
        'end_time' => '12:45',
    ]);

    // When: calculating hours worked.
    // Then: returns 4.25hours (4 hours 15 minutes).
    expect($timesheet->hours_worked)->toBe(4.25);
});

test('CP-03_EIF-25 - preserves is_holiday flag on timesheet instance', function () {
    // Given: a timesheet entry flagged as holiday.
    $timesheet = Timesheet::make([
        'start_time' => '08:00',
        'end_time' => '17:00',
        'is_holiday' => true,
    ]);

    // When: accessing the is_holiday attribute.
    // Then: holiday flag is preserved.
    expect($timesheet->is_holiday)->toBeTrue();
});

test('CP-04_EIF-25 - non-holiday timesheet defaults is_holiday to false or null', function () {
    // Given: a timesheet without explicit is_holiday flag.
    $timesheet = Timesheet::make([
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    // When: accessing is_holiday.
    // Then: is_holiday is either false or null (model default behavior).
    expect(in_array($timesheet->is_holiday, [false, null]))->toBeTrue();
});

test('CP-05_EIF-25 - timesheet model accepts all required fields', function () {
    // Given: complete timesheet data with all required fields.
    $data = [
        'employee_id' => 1,
        'work_date' => now()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '17:00',
        'is_holiday' => false,
    ];

    // When: creating timesheet with complete data.
    $timesheet = Timesheet::make($data);

    // Then: all properties are set and accessible.
    expect($timesheet->employee_id)->toBe(1)
        ->and($timesheet->work_date)->not->toBeNull()
        ->and($timesheet->start_time)->not->toBeNull()
        ->and($timesheet->end_time)->not->toBeNull()
        ->and($timesheet->is_holiday)->toBeFalse();
});

test('CP-06_EIF-25 - timesheet correctly identifies holiday from is_holiday field', function () {
    // Given: two timesheets, one holiday and one regular.
    $holiday = Timesheet::make(['is_holiday' => true]);
    $regular = Timesheet::make(['is_holiday' => false]);

    // When: checking is_holiday property.
    // Then: correctly identifies each type.
    expect($holiday->is_holiday)->toBeTrue()
        ->and($regular->is_holiday)->toBeFalse();
});
