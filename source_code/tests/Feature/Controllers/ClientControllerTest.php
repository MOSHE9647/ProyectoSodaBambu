<?php

namespace Tests\Feature\Controllers;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_success_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $response = $this->get(route('clients.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_store_creates_new_client(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $payload = [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'phone' => '+506 7777 1234',
            'email' => 'ana.lopez@example.com',
        ];

        $response = $this->post(route('clients.store'), $payload);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'email' => 'ana.lopez@example.com',
            'first_name' => 'Ana',
            'deleted_at' => null,
        ]);
    }

    public function test_store_restores_soft_deleted_client_with_same_email(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $client = Client::factory()->create([
            'first_name' => 'Viejo',
            'last_name' => 'Cliente',
            'email' => 'reuse-client@example.com',
        ]);
        $originalId = $client->id;
        $client->delete();

        $response = $this->post(route('clients.store'), [
            'first_name' => 'Nuevo',
            'last_name' => 'Cliente',
            'phone' => '+506 6000 6000',
            'email' => 'reuse-client@example.com',
        ]);

        $response->assertRedirect(route('clients.index'));

        $restored = Client::withTrashed()->where('email', 'reuse-client@example.com')->first();

        $this->assertNotNull($restored);
        $this->assertSame($originalId, $restored->id);
        $this->assertNull($restored->deleted_at);
        $this->assertSame('Nuevo', $restored->first_name);
        $this->assertSame('+506 6000 6000', $restored->phone);
    }

    public function test_destroy_soft_deletes_client(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $client = Client::factory()->create();

        $response = $this->from(route('clients.index'))
            ->delete(route('clients.destroy', $client));

        $response->assertRedirect(route('clients.index'));
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_update_allows_partial_update_for_existing_client(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $client = Client::factory()->create([
            'first_name' => 'Mario',
            'last_name' => 'Perez',
            'email' => 'mario@example.com',
        ]);

        $response = $this->put(route('clients.update', $client), [
            'last_name' => 'Ramirez',
        ]);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'first_name' => 'Mario',
            'last_name' => 'Ramirez',
            'email' => 'mario@example.com',
        ]);
    }

    public function test_update_rejects_duplicate_active_client_email(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        Client::factory()->create(['email' => 'repetido@example.com']);
        $client = Client::factory()->create(['email' => 'cliente@example.com']);

        $response = $this->from(route('clients.edit', $client))
            ->put(route('clients.update', $client), [
                'email' => 'repetido@example.com',
            ]);

        $response->assertRedirect(route('clients.edit', $client));
        $response->assertSessionHasErrors('email');
    }
}
