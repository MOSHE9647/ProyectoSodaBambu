<?php

use App\Http\Requests\CategoryRequest;
use Illuminate\Support\Facades\Validator;

test('CP-01_EIF-01 - validates category creation request', function () {
    // GIVEN: a payload with an empty name field
    $payLoad = [
        'name' => '',
        'description' => 'Categoría de bebidas',
    ];

    // WHEN: validating the payload against the CategoryRequest rules
    $rules = (new CategoryRequest)->rules();
    $validator = Validator::make($payLoad, $rules);

    // THEN: validation should fail and return an error for the name field
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->keys())->toContain('name');
});
