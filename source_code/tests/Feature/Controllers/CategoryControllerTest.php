<?php

namespace Tests\Feature\Controllers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_success_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $response = $this->get(route('categories.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_store_creates_new_category(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $payload = [
            'name' => 'Bebidas Frias',
            'description' => 'Gaseosas y jugos',
        ];

        $response = $this->post(route('categories.store'), $payload);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Bebidas Frias',
            'description' => 'Gaseosas y jugos',
            'deleted_at' => null,
        ]);
    }

    public function test_store_restores_soft_deleted_category_with_same_name(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $category = Category::factory()->create([
            'name' => 'Entradas',
            'description' => 'Descripcion vieja',
        ]);
        $originalId = $category->id;
        $category->delete();

        $response = $this->post(route('categories.store'), [
            'name' => 'Entradas',
            'description' => 'Descripcion nueva',
        ]);

        $response->assertRedirect(route('categories.index'));

        $restored = Category::withTrashed()->where('name', 'Entradas')->first();

        $this->assertNotNull($restored);
        $this->assertSame($originalId, $restored->id);
        $this->assertNull($restored->deleted_at);
        $this->assertSame('Descripcion nueva', $restored->description);
    }

    public function test_destroy_soft_deletes_category(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $category = Category::factory()->create();

        $response = $this->from(route('categories.index'))
            ->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_update_modifies_existing_category(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $category = Category::factory()->create([
            'name' => 'Comidas',
            'description' => 'Descripcion inicial',
        ]);

        $response = $this->put(route('categories.update', $category), [
            'name' => 'Comidas',
            'description' => 'Descripcion actualizada',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Comidas',
            'description' => 'Descripcion actualizada',
        ]);
    }

    public function test_update_rejects_duplicate_active_category_name(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        Category::factory()->create(['name' => 'Nombre Unico']);
        $category = Category::factory()->create(['name' => 'Otro Nombre']);

        $response = $this->from(route('categories.edit', $category))
            ->put(route('categories.update', $category), [
                'name' => 'Nombre Unico',
                'description' => 'Intento duplicado',
            ]);

        $response->assertRedirect(route('categories.edit', $category));
        $response->assertSessionHasErrors('name');
    }
}
