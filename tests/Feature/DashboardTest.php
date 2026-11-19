<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pruebas de integración para el Panel de Control (Dashboard).
 *
 * Verifica la autenticación, carga del componente y las métricas
 * de envíos de formularios proporcionadas al frontend.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->user = User::factory()->create(['active' => true]);
        $this->user->assignRole('admin');
    }

    #[Test]
    public function dashboard_requiere_autenticacion(): void
    {
        $response = $this->get('/panel');
        $response->assertRedirect('/acceso');
    }

    #[Test]
    public function dashboard_muestra_estadisticas_de_envios_y_alertas(): void
    {
        $form = Form::create([
            'name'          => 'Formulario Contacto',
            'slug'          => 'contacto',
            'fields_schema' => [['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre']],
        ]);

        // Crear 1 leído y 2 no leídos
        $form->submissions()->create(['data' => ['nombre' => 'Leído'], 'ip_address' => '1.1.1.1', 'is_read' => true]);
        $form->submissions()->create(['data' => ['nombre' => 'No leído 1'], 'ip_address' => '1.1.1.2', 'is_read' => false]);
        $form->submissions()->create(['data' => ['nombre' => 'No leído 2'], 'ip_address' => '1.1.1.3', 'is_read' => false]);

        $response = $this->actingAs($this->user)->get('/panel');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Dashboard')
                 ->has('estadisticasEnvios')
                 ->where('estadisticasEnvios.total', 3)
                 ->where('estadisticasEnvios.no_leidos', 2)
                 ->has('estadisticasEnvios.ultimo_envio')
        );
    }
}
