<?php

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-03_EIF-20_QA2 - employee request authorize and rules resolve with route user id', function () {
    // Given: a route-bound user for update scenario.
    $user = User::factory()->create();

    $request = new class($user) extends EmployeeRequest
    {
        public function __construct(private readonly User $routeUser) {}

        public function route($param = null, $default = null): mixed
        {
            if ($param === 'user') {
                return $this->routeUser;
            }

            return $default;
        }
    };

    // When: checking authorization and retrieving rules.
    $authorized = $request->authorize();
    $rules = $request->rules();

    // Then: request is authorized and employee fields are present in rules.
    expect($authorized)->toBeTrue();
    expect($rules)->toHaveKeys(['phone', 'status', 'hourly_wage', 'payment_frequency']);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-03_EIF-20_QA2 - employee rules require phone status wage and payment frequency', function () {
    // Given: an empty payload for employee data.
    $payload = [];

    // When: validating with employee rules.
    $validator = Validator::make($payload, EmployeeRequest::rulesFor());

    // Then: required field errors are returned for all employee fields.
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->keys())->toContain('phone');
    expect($validator->errors()->keys())->toContain('status');
    expect($validator->errors()->keys())->toContain('hourly_wage');
    expect($validator->errors()->keys())->toContain('payment_frequency');
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-01_EIF-20_QA2 - employee phone must be unique among active rows and can be reused after soft delete', function () {
    // Given: one active employee and one soft-deleted employee with known phones.
    $activeUser = User::factory()->create();
    Employee::factory()->create([
        'id' => $activeUser->id,
        'phone' => '506-8888-0001',
    ]);

    $deletedUser = User::factory()->create();
    $deletedEmployee = Employee::factory()->create([
        'id' => $deletedUser->id,
        'phone' => '506-8888-0002',
    ]);
    $deletedEmployee->delete();

    // When: validating payloads with duplicate active vs duplicate soft-deleted phone.
    $duplicateActive = Validator::make([
        'phone' => '506-8888-0001',
        'status' => EmployeeStatus::ACTIVE->value,
        'hourly_wage' => 2000,
        'payment_frequency' => PaymentFrequency::MONTHLY->value,
    ], EmployeeRequest::rulesFor());

    $duplicateDeleted = Validator::make([
        'phone' => '506-8888-0002',
        'status' => EmployeeStatus::ACTIVE->value,
        'hourly_wage' => 2000,
        'payment_frequency' => PaymentFrequency::MONTHLY->value,
    ], EmployeeRequest::rulesFor());

    // Then: active duplicate fails, soft-deleted duplicate passes.
    expect($duplicateActive->fails())->toBeTrue();
    expect($duplicateActive->errors()->keys())->toContain('phone');

    expect($duplicateDeleted->fails())->toBeFalse();
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-04_EIF-20_QA2 - employee rules ignore current user id on update and enforce enums', function () {
    // Given: an existing employee user being updated with same phone.
    $user = User::factory()->create();
    Employee::factory()->create([
        'id' => $user->id,
        'phone' => '506-8888-0003',
    ]);

    // When: validating update payload with same phone and valid enum values.
    $validUpdate = Validator::make([
        'phone' => '506-8888-0003',
        'status' => EmployeeStatus::ACTIVE->value,
        'hourly_wage' => 2500,
        'payment_frequency' => PaymentFrequency::BIWEEKLY->value,
    ], EmployeeRequest::rulesFor($user->id));

    $invalidEnums = Validator::make([
        'phone' => '506-8888-1234',
        'status' => 'invalid-status',
        'hourly_wage' => 2500,
        'payment_frequency' => 'invalid-frequency',
    ], EmployeeRequest::rulesFor($user->id));

    // Then: same phone for same user is allowed, invalid enums are rejected.
    expect($validUpdate->fails())->toBeFalse();

    expect($invalidEnums->fails())->toBeTrue();
    expect($invalidEnums->errors()->keys())->toContain('status');
    expect($invalidEnums->errors()->keys())->toContain('payment_frequency');
});
