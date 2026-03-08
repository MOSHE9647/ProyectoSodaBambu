<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UserRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;
use PHPUnit\Framework\TestCase;

class UserRequestTest extends TestCase
{
    public function test_rules_include_expected_user_fields_and_constraints(): void
    {
        $request = new UserRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('role', $rules);
        $this->assertArrayHasKey('password', $rules);

        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);

        $emailRules = $rules['email'];
        $this->assertContains('required', $emailRules);
        $this->assertContains('email', $emailRules);
        $this->assertTrue(collect($emailRules)->contains(fn ($rule) => $rule instanceof Unique));

        $this->assertTrue(collect($rules['role'])->contains(fn ($rule) => $rule instanceof Enum));
        $this->assertContains('nullable', $rules['password']);
        $this->assertContains('confirmed', $rules['password']);
    }
}
