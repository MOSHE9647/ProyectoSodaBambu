<?php

use App\Casts\DecimalFormat;
use App\Models\Employee;

/**
 * Unit Story: Decimal Format Cast for Currency Display
 * Tests the DecimalFormat cast behavior for formatting numeric values
 * using Costa Rican currency conventions (comma for decimals, dot for thousands).
 */
test('CP-01_EIF-22_QA3 - formats decimal values with comma separator and dot for thousands', function () {
    // Given: a value to be formatted (e.g., salary amount 5000.00)
    $cast = new DecimalFormat;
    $model = Employee::factory()->create();

    // When: the get method is called with a numeric value
    $formatted = $cast->get($model, 'hourly_wage', '5000.50', []);

    // Then: the value is formatted as "5.000,50" (Costa Rican format: . for thousands, , for decimals)
    expect($formatted)->toBe('5.000,50');
});

test('CP-02_EIF-22_QA3 - handles integer values and formats them with zeros', function () {
    // Given: an integer value without decimals
    $cast = new DecimalFormat;
    $model = Employee::factory()->create();

    // When: the get method is called with integer
    $formatted = $cast->get($model, 'hourly_wage', 1000, []);

    // Then: the value includes two decimal places
    expect($formatted)->toBe('1.000,00');
});

test('CP-03_EIF-22_QA3 - formats zero value correctly', function () {
    // Given: zero as input
    $cast = new DecimalFormat;
    $model = Employee::factory()->create();

    // When: the get method is called with zero
    $formatted = $cast->get($model, 'hourly_wage', 0, []);

    // Then: the value is formatted as "0,00"
    expect($formatted)->toBe('0,00');
});

test('CP-04_EIF-22_QA3 - formats large numbers with thousands separators', function () {
    // Given: a large monetary value
    $cast = new DecimalFormat;
    $model = Employee::factory()->create();

    // When: the get method is called with large value
    $formatted = $cast->get($model, 'hourly_wage', 1234567.89, []);

    // Then: the value includes dot separators for thousands and comma for decimals
    expect($formatted)->toBe('1.234.567,89');
});

test('CP-05_EIF-22_QA3 - returns value as-is in set method', function () {
    // Given: a value to be saved to the database
    $cast = new DecimalFormat;
    $model = Employee::factory()->create();

    // When: the set method is called
    $result = $cast->set($model, 'hourly_wage', 5000.50, []);

    // Then: the value is returned unchanged (no transformation on storage)
    expect($result)->toBe(5000.50);
});

test('CP-06_EIF-22_QA3 - handles string numeric input in set method', function () {
    // Given: a string representation of a number
    $cast = new DecimalFormat;
    $model = Employee::factory()->create();

    // When: the set method is called with string
    $result = $cast->set($model, 'hourly_wage', '5000.50', []);

    // Then: the value is returned as-is (caller responsibility to validate)
    expect($result)->toBe('5000.50');
});

test('CP-07_EIF-22_QA3 - formats decimal with more than 2 places', function () {
    // Given: a value with many decimal places
    $cast = new DecimalFormat;
    $model = Employee::factory()->create();

    // When: the get method is called with many decimals
    $formatted = $cast->get($model, 'hourly_wage', 1234.56789, []);

    // Then: the value is rounded to 2 decimal places
    expect($formatted)->toBe('1.234,57');
});
