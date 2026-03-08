<?php

namespace Tests\Feature\Controllers;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_success_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $response = $this->get(route('suppliers.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_store_creates_new_supplier(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $payload = [
            'name' => 'Proveedor Uno',
            'phone' => '+506 2222 3333',
            'email' => 'proveedor1@example.com',
        ];

        $response = $this->post(route('suppliers.store'), $payload);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'email' => 'proveedor1@example.com',
            'name' => 'Proveedor Uno',
            'deleted_at' => null,
        ]);
    }

    public function test_store_restores_soft_deleted_supplier_with_same_email(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $supplier = Supplier::factory()->create([
            'name' => 'Proveedor Viejo',
            'phone' => '+506 1111 1111',
            'email' => 'reuse-supplier@example.com',
        ]);
        $originalId = $supplier->id;
        $supplier->delete();

        $response = $this->post(route('suppliers.store'), [
            'name' => 'Proveedor Nuevo',
            'phone' => '+506 9999 9999',
            'email' => 'reuse-supplier@example.com',
        ]);

        $response->assertRedirect(route('suppliers.index'));

        $restored = Supplier::withTrashed()->where('email', 'reuse-supplier@example.com')->first();

        $this->assertNotNull($restored);
        $this->assertSame($originalId, $restored->id);
        $this->assertNull($restored->deleted_at);
        $this->assertSame('Proveedor Nuevo', $restored->name);
        $this->assertSame('+506 9999 9999', $restored->phone);
    }

    public function test_destroy_soft_deletes_supplier(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('suppliers.destroy', $supplier));

        $response->assertRedirect(route('suppliers.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_update_modifies_existing_supplier(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $supplier = Supplier::factory()->create([
            'name' => 'Proveedor Inicial',
            'phone' => '+506 2000 0000',
            'email' => 'proveedor.inicial@example.com',
        ]);

        $response = $this->put(route('suppliers.update', $supplier), [
            'name' => 'Proveedor Actualizado',
            'phone' => '+506 3000 0000',
            'email' => 'proveedor.inicial@example.com',
        ]);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Proveedor Actualizado',
            'phone' => '+506 3000 0000',
        ]);
    }

    public function test_update_rejects_duplicate_active_supplier_email(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        Supplier::factory()->create(['email' => 'duplicado@example.com']);
        $supplier = Supplier::factory()->create(['email' => 'origen@example.com']);

        $response = $this->from(route('suppliers.edit', $supplier))
            ->put(route('suppliers.update', $supplier), [
                'name' => 'Proveedor',
                'phone' => '+506 4444 4444',
                'email' => 'duplicado@example.com',
            ]);

        $response->assertRedirect(route('suppliers.edit', $supplier));
        $response->assertSessionHasErrors('email');
    }
}
