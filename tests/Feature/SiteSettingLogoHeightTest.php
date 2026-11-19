<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SiteSettingLogoHeightTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_configuracion_guarda_y_expone_altura_de_logotipo_en_escritorio_y_movil(): void
    {
        SiteSetting::cached();

        Page::create([
            'title'       => 'Inicio',
            'slug'        => '',
            'is_active'   => true,
            'is_homepage' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('configuracion.update'), [
            'site_name'          => 'Mi Marca',
            'primary_color'      => '#10b981',
            'heading_font'       => 'Hanken Grotesk',
            'body_font'          => 'Hanken Grotesk',
            'logo_height'        => 56,
            'logo_height_mobile' => 38,
        ]);

        $response->assertRedirect(route('configuracion.index'));

        $settings = SiteSetting::cached();
        $this->assertSame(56, $settings->logo_height);
        $this->assertSame(38, $settings->logo_height_mobile);

        $public = $this->get('/');
        $public->assertOk()
            ->assertSee('--logo-height: 56px;', false)
            ->assertSee('--logo-height-mobile: 38px;', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('siteSettings.logo_height', 56)
                ->where('siteSettings.logo_height_mobile', 38)
            );
    }

    public function test_configuracion_valida_rango_de_altura_del_logotipo(): void
    {
        $response = $this->actingAs($this->admin)->put(route('configuracion.update'), [
            'site_name'          => 'Mi Marca',
            'primary_color'      => '#10b981',
            'logo_height'        => 300,
            'logo_height_mobile' => 8,
        ]);

        $response->assertSessionHasErrors(['logo_height', 'logo_height_mobile']);
    }
}
