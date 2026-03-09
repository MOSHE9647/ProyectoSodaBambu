<?php

namespace Tests\Feature\Requests;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());
    }

    public function test_store_validates_required_name_type_and_max_length(): void
    {
        $this->actingAsAdmin();

        $missingName = $this->from(route('categories.create'))->post(route('categories.store'), [
            'name' => '',
            'description' => 'Desc',
        ]);
        $missingName->assertRedirect(route('categories.create'));
        $missingName->assertSessionHasErrors('name');

        $nameTooLong = $this->from(route('categories.create'))->post(route('categories.store'), [
            'name' => str_repeat('a', 256),
            'description' => 'Desc',
        ]);
        $nameTooLong->assertRedirect(route('categories.create'));
        $nameTooLong->assertSessionHasErrors('name');
    }

    public function test_store_validates_unique_name_and_description_max_length(): void
    {
        $this->actingAsAdmin();

        Category::factory()->create(['name' => 'Unica']);

        $duplicateName = $this->from(route('categories.create'))->post(route('categories.store'), [
            'name' => 'Unica',
            'description' => 'Desc',
        ]);
        $duplicateName->assertRedirect(route('categories.create'));
        $duplicateName->assertSessionHasErrors('name');

        $descriptionTooLong = $this->from(route('categories.create'))->post(route('categories.store'), [
            'name' => 'Nueva Categoria',
            'description' => str_repeat('d', 256),
        ]);
        $descriptionTooLong->assertRedirect(route('categories.create'));
        $descriptionTooLong->assertSessionHasErrors('description');
    }
}
