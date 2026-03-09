<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\SupplierRequest;
use Illuminate\Validation\Rules\Unique;
use PHPUnit\Framework\TestCase;

class SupplierRequestTest extends TestCase
{
    public function test_rules_include_expected_fields_and_unique_constraint(): void
    {
        $request = new SupplierRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('email', $rules);

        $this->assertSame('required|string|max:255', $rules['name']);
        $this->assertSame('required|string|max:20', $rules['phone']);

        $emailRules = $rules['email'];
        $this->assertContains('required', $emailRules);
        $this->assertContains('email', $emailRules);
        $this->assertContains('max:255', $emailRules);
        $this->assertTrue(collect($emailRules)->contains(fn ($rule) => $rule instanceof Unique));
    }
}
