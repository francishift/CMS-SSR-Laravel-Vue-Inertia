<?php

namespace Tests\Feature;

use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PageTitleSizeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_pagina_crea_title_size_default_y_permite_actualizarlo(): void
    {
        $storeResponse = $this->actingAs($this->admin)->post(route('paginas.store'), [
            'title'     => 'Política de Privacidad',
            'slug'      => 'politica-de-privacidad',
            'body'      => '<p>Texto legal</p>',
            'is_active' => true,
            'order'     => 1,
        ]);

        $storeResponse->assertRedirect(route('paginas.index'));

        $pagina = Page::where('slug', 'politica-de-privacidad')->firstOrFail();
        $this->assertSame('default', $pagina->title_size);

        $updateResponse = $this->actingAs($this->admin)->put(route('paginas.update', $pagina), [
            'title'      => 'Política de Privacidad',
            'title_size' => 'h3',
            'slug'       => 'politica-de-privacidad',
            'body'       => '<p>Texto legal actualizado</p>',
            'is_active'  => true,
            'order'      => 1,
        ]);

        $updateResponse->assertRedirect(route('paginas.index'));
        $this->assertSame('h3', $pagina->fresh()->title_size);

        $resolved = (new PageResource($pagina->fresh()))->resolve();
        $this->assertSame('h3', $resolved['title_size']);

        $publicResponse = $this->get('/politica-de-privacidad');
        $publicResponse->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Page')
                ->where('pagina.title_size', 'h3')
            );
    }

    public function test_pagina_rechaza_title_size_invalido(): void
    {
        $response = $this->actingAs($this->admin)->post(route('paginas.store'), [
            'title'      => 'Política de Cookies',
            'title_size' => 'gigante-invalido',
            'slug'       => 'politica-de-cookies',
            'body'       => '<p>Cookies</p>',
            'is_active'  => true,
        ]);

        $response->assertSessionHasErrors(['title_size']);
    }
}
