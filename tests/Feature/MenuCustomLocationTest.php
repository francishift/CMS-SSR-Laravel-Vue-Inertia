<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuCustomLocationTest extends TestCase
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

    /**
     * Comprobar que se puede crear un menú con ubicación 'custom' (Sin asignar / Solo Page Builder).
     */
    public function test_crear_menu_con_ubicacion_custom(): void
    {
        $response = $this->actingAs($this->admin)->post(route('menus.store'), [
            'name'      => 'Menú Enlaces Footer',
            'location'  => 'custom',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('menus.index'));

        $this->assertDatabaseHas('menus', [
            'name'     => 'Menú Enlaces Footer',
            'location' => 'custom',
        ]);
    }

    /**
     * Comprobar que se puede cambiar la ubicación de un menú existente de sidebar a custom.
     */
    public function test_cambiar_ubicacion_de_sidebar_a_custom(): void
    {
        $menu = Menu::create([
            'name'      => 'Menú Lateral',
            'slug'      => 'menu-lateral',
            'location'  => 'sidebar',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('menus.update', $menu), [
            'name'      => 'Menú Lateral',
            'slug'      => 'menu-lateral',
            'location'  => 'custom',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('menus', [
            'id'       => $menu->id,
            'location' => 'custom',
        ]);
    }

    /**
     * Comprobar que los menús custom no interfieren en porUbicacion('sidebar') ni porUbicacion('header').
     */
    public function test_menu_custom_no_interfiere_con_header_o_sidebar(): void
    {
        Menu::create([
            'name'      => 'Menú Cabecera',
            'slug'      => 'menu-cabecera',
            'location'  => 'header',
            'is_active' => true,
        ]);

        Menu::create([
            'name'      => 'Menú Independiente',
            'slug'      => 'menu-independiente',
            'location'  => 'custom',
            'is_active' => true,
        ]);

        $headerMenu = Menu::porUbicacion('header');
        $this->assertNotNull($headerMenu);
        $this->assertEquals('menu-cabecera', $headerMenu->slug);

        $sidebarMenu = Menu::porUbicacion('sidebar');
        $this->assertNull($sidebarMenu);
    }

    /**
     * Comprobar que activosEstructurados incluye menús custom y funciona con caché serializada.
     */
    public function test_activos_estructurados_incluye_custom_y_persiste_en_cache(): void
    {
        $custom = Menu::create([
            'name'      => 'Menú Enlaces Útiles',
            'slug'      => 'menu-enlaces-utiles',
            'location'  => 'custom',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id'   => $custom->id,
            'title'     => 'Aviso Legal',
            'url'       => '/aviso-legal',
            'order'     => 1,
            'is_active' => true,
        ]);

        $primeraLlamada = Menu::activosEstructurados();
        $this->assertCount(1, $primeraLlamada);
        $this->assertEquals('Menú Enlaces Útiles', $primeraLlamada[0]['name']);
        $this->assertEquals('custom', $primeraLlamada[0]['location']);
        $this->assertCount(1, $primeraLlamada[0]['items']);
        $this->assertEquals('Aviso Legal', $primeraLlamada[0]['items'][0]['title']);

        // Segunda llamada desde la caché
        $segundaLlamada = Menu::activosEstructurados();
        $this->assertIsArray($segundaLlamada);
        $this->assertCount(1, $segundaLlamada);
        $this->assertEquals('Menú Enlaces Útiles', $segundaLlamada[0]['name']);
    }
}
