<?php

namespace Tests\Feature\Controllers;

use App\Models\BuilderLibraryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderLibraryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Crear usuario admin con rol
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_list_library_items(): void
    {
        BuilderLibraryItem::create([
            'name'         => 'Grid Test',
            'type'         => 'grid_articles',
            'category'     => 'grids',
            'content_json' => ['mode' => 'dynamic'],
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('builder-library.index'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.name', 'Grid Test');
    }

    public function test_admin_can_store_library_item(): void
    {
        $payload = [
            'name'         => 'Encabezado Hero Custom',
            'type'         => 'heading',
            'category'     => 'headers',
            'description'  => 'Encabezado con estilo Hero',
            'content_json' => ['text' => 'Hola mundo', 'tag' => 'h1'],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('builder-library.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('item.name', 'Encabezado Hero Custom');

        $this->assertDatabaseHas('builder_library_items', [
            'name' => 'Encabezado Hero Custom',
            'type' => 'heading',
        ]);
    }

    public function test_admin_can_delete_library_item(): void
    {
        $item = BuilderLibraryItem::create([
            'name'         => 'A Borrar',
            'type'         => 'text',
            'category'     => 'general',
            'content_json' => ['html' => '<p>Test</p>'],
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('builder-library.destroy', $item->id));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('builder_library_items', [
            'id' => $item->id,
        ]);
    }
}
