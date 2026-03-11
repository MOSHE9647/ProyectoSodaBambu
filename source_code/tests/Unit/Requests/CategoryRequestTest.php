<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\CategoryRequest;
use Illuminate\Validation\Rules\Unique;
use PHPUnit\Framework\TestCase;

class CategoryRequestTest extends TestCase
{
    public function test_rules_include_expected_fields_and_unique_constraint(): void
    {
        $request = new CategoryRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);

        $nameRules = $rules['name'];
        $this->assertContains('required', $nameRules);
        $this->assertContains('string', $nameRules);
        $this->assertContains('max:255', $nameRules);
        $this->assertTrue(collect($nameRules)->contains(fn ($rule) => $rule instanceof Unique));
    }
}
