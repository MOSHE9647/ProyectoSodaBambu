<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ClientRequest;
use Illuminate\Validation\Rules\Unique;
use PHPUnit\Framework\TestCase;

class ClientRequestTest extends TestCase
{
    public function test_post_rules_require_core_fields(): void
    {
        $request = ClientRequest::create('/clients', 'POST');
        $rules = $request->rules();

        $this->assertContains('required', $rules['first_name']);
        $this->assertContains('required', $rules['last_name']);
        $this->assertContains('required', $rules['email']);
        $this->assertTrue(collect($rules['email'])->contains(fn ($rule) => $rule instanceof Unique));
    }

    public function test_put_rules_allow_partial_updates(): void
    {
        $request = ClientRequest::create('/clients/1', 'PUT');
        $rules = $request->rules();

        $this->assertContains('sometimes', $rules['first_name']);
        $this->assertContains('sometimes', $rules['last_name']);
        $this->assertContains('sometimes', $rules['email']);
    }
}
