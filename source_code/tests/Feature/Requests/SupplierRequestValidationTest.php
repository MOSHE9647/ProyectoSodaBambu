<?php

namespace Tests\Feature\Requests;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());
    }

    public function test_store_validates_name_and_phone_required_and_max(): void
    {
        $this->actingAsAdmin();

        $missingName = $this->from(route('suppliers.create'))->post(route('suppliers.store'), [
            'name' => '',
            'phone' => '+506 2222 3333',
            'email' => 'supplier.req@example.com',
        ]);
        $missingName->assertRedirect(route('suppliers.create'));
        $missingName->assertSessionHasErrors('name');

        $phoneTooLong = $this->from(route('suppliers.create'))->post(route('suppliers.store'), [
            'name' => 'Proveedor',
            'phone' => str_repeat('1', 21),
            'email' => 'supplier.phone.max@example.com',
        ]);
        $phoneTooLong->assertRedirect(route('suppliers.create'));
        $phoneTooLong->assertSessionHasErrors('phone');
    }

    public function test_store_validates_email_format_max_and_unique(): void
    {
        $this->actingAsAdmin();

        Supplier::factory()->create(['email' => 'duplicated.supplier@example.com']);

        $invalidEmail = $this->from(route('suppliers.create'))->post(route('suppliers.store'), [
            'name' => 'Proveedor',
            'phone' => '+506 1111 2222',
            'email' => 'invalid-email',
        ]);
        $invalidEmail->assertRedirect(route('suppliers.create'));
        $invalidEmail->assertSessionHasErrors('email');

        $emailTooLong = $this->from(route('suppliers.create'))->post(route('suppliers.store'), [
            'name' => 'Proveedor',
            'phone' => '+506 1111 2222',
            'email' => str_repeat('a', 250) . '@x.com',
        ]);
        $emailTooLong->assertRedirect(route('suppliers.create'));
        $emailTooLong->assertSessionHasErrors('email');

        $duplicateEmail = $this->from(route('suppliers.create'))->post(route('suppliers.store'), [
            'name' => 'Proveedor',
            'phone' => '+506 1111 2222',
            'email' => 'duplicated.supplier@example.com',
        ]);
        $duplicateEmail->assertRedirect(route('suppliers.create'));
        $duplicateEmail->assertSessionHasErrors('email');
    }
}
