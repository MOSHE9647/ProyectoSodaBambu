<?php

namespace Tests\Feature\Requests;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());
    }

    public function test_store_validates_required_names_and_email_format(): void
    {
        $this->actingAsAdmin();

        $missingNames = $this->from(route('clients.create'))->post(route('clients.store'), [
            'first_name' => '',
            'last_name' => '',
            'email' => 'cliente@example.com',
        ]);
        $missingNames->assertRedirect(route('clients.create'));
        $missingNames->assertSessionHasErrors(['first_name', 'last_name']);

        $invalidEmail = $this->from(route('clients.create'))->post(route('clients.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'email' => 'not-an-email',
        ]);
        $invalidEmail->assertRedirect(route('clients.create'));
        $invalidEmail->assertSessionHasErrors('email');
    }

    public function test_store_validates_unique_email_and_phone_max_length(): void
    {
        $this->actingAsAdmin();

        Client::factory()->create(['email' => 'duplicated.client@example.com']);

        $duplicateEmail = $this->from(route('clients.create'))->post(route('clients.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'email' => 'duplicated.client@example.com',
        ]);
        $duplicateEmail->assertRedirect(route('clients.create'));
        $duplicateEmail->assertSessionHasErrors('email');

        $phoneTooLong = $this->from(route('clients.create'))->post(route('clients.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'email' => 'phone.max.client@example.com',
            'phone' => str_repeat('1', 21),
        ]);
        $phoneTooLong->assertRedirect(route('clients.create'));
        $phoneTooLong->assertSessionHasErrors('phone');
    }

    public function test_update_validates_email_only_when_provided_and_applies_unique(): void
    {
        $this->actingAsAdmin();

        Client::factory()->create(['email' => 'dup.update.client@example.com']);
        $client = Client::factory()->create(['email' => 'current.update.client@example.com']);

        $invalidEmail = $this->from(route('clients.edit', $client))
            ->put(route('clients.update', $client), [
                'email' => 'invalid-email',
            ]);
        $invalidEmail->assertRedirect(route('clients.edit', $client));
        $invalidEmail->assertSessionHasErrors('email');

        $duplicateEmail = $this->from(route('clients.edit', $client))
            ->put(route('clients.update', $client), [
                'email' => 'dup.update.client@example.com',
            ]);
        $duplicateEmail->assertRedirect(route('clients.edit', $client));
        $duplicateEmail->assertSessionHasErrors('email');
    }
}
