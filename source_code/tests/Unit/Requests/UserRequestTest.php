<?php

use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Validator;

test('CP-FUN-01 - validates an invalid request data fails validation', function () {
    // Given: A payload with invalid data for creating a user.
    $payload = [
        'name' => '', // Name is required, so this is invalid.
        'email' => 'invalid-email', // Invalid email format.
        'role' => 'invalid-role', // Not a valid role.
        'password' => 'short', // Password too short.
    ];

    // When: We attempt to validate the request with the UserRequest rules.
    $rules = (new UserRequest)->rules();
    $validator = Validator::make($payload, $rules);

    // Then: The validation should fail and return errors for the invalid fields.
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->has('role'))->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
})->group('s7-tests');
