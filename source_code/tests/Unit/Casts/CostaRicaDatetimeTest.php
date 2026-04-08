<?php

use App\Casts\CostaRicaDatetime;
use App\Models\User;
use Carbon\Carbon;

/**
 * Unit Story: Costa Rica Datetime Cast
 * Tests the CostaRicaDatetime cast for handling various datetime input types
 * and timezone conversions between Costa Rica and UTC.
 */
test('CP-01_EIF-22_QA4 - detects and formats date-only strings (YYYY-MM-DD)', function () {
    // Given: a date string without time component
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();

    // When: the get method is called with a date string
    $result = $cast->get($user, 'created_at', '2025-03-15', []);

    // Then: the result is formatted as ISO date (YYYY-MM-DD)
    expect($result)->toBe('2025-03-15');
});

test('CP-02_EIF-22_QA4 - detects and formats timestamp (integer seconds)', function () {
    // Given: a Unix timestamp in seconds
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();
    $timestamp = 1678900000; // arbitrary unix timestamp

    // When: the get method is called with integer timestamp
    $result = $cast->get($user, 'created_at', $timestamp, []);

    // Then: the result is formatted as ISO 8601 UTC (with Z suffix)
    expect($result)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/');
});

test('CP-03_EIF-22_QA4 - detects and formats datetime strings with time component', function () {
    // Given: a datetime string in any parseable format
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();

    // When: the get method is called with datetime string
    $result = $cast->get($user, 'created_at', '2025-03-15 14:30:00', []);

    // Then: the result is formatted as ISO 8601 with timezone
    expect($result)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/');
});

test('CP-04_EIF-22_QA4 - returns null when value is null in get method', function () {
    // Given: a null value
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();

    // When: get method is called with null
    $result = $cast->get($user, 'created_at', null, []);

    // Then: returns null
    expect($result)->toBeNull();
});

test('CP-05_EIF-22_QA4 - converts Costa Rica datetime to UTC for storage in set method', function () {
    // Given: a datetime string in Costa Rica timezone (UTC-6)
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();
    $costaRicaTime = '2025-03-15 14:30:00'; // in America/Costa_Rica TZ

    // When: the set method is called
    $result = $cast->set($user, 'created_at', $costaRicaTime, []);

    // Then: the result is stored in UTC format
    expect($result)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
});

test('CP-06_EIF-22_QA4 - handles Carbon instance in set method', function () {
    // Given: a Carbon instance
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();
    $carbonDate = Carbon::parse('2025-03-15 14:30:00', 'America/Costa_Rica');

    // When: the set method is called with Carbon instance
    $result = $cast->set($user, 'created_at', $carbonDate, []);

    // Then: the result is UTC-formatted string
    expect($result)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
});

test('CP-07_EIF-22_QA4 - returns null when value is null in set method', function () {
    // Given: a null value to be stored
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();

    // When: set method is called with null
    $result = $cast->set($user, 'created_at', null, []);

    // Then: returns null
    expect($result)->toBeNull();
});

test('CP-08_EIF-22_QA4 - converts timestamp integer to UTC datetime in set method', function () {
    // Given: a Unix timestamp (seconds since epoch)
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();
    $timestamp = 1678900000;

    // When: the set method is called with timestamp
    $result = $cast->set($user, 'created_at', $timestamp, []);

    // Then: the result is converted to UTC formatted string
    expect($result)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
});

test('CP-09_EIF-22_QA4 - formats date-only input in set method', function () {
    // Given: a date-only string (YYYY-MM-DD)
    $cast = new CostaRicaDatetime;
    $user = User::factory()->create();

    // When: the set method is called
    $result = $cast->set($user, 'created_at', '2025-03-15', []);

    // Then: result is formatted as date only
    expect($result)->toBe('2025-03-15');
});
