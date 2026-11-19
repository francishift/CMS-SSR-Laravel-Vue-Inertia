<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Tests del flujo OAuth con Google.
 *
 * Valida:
 * - Redirección a Google funciona correctamente.
 * - Login exitoso con email existente y usuario activo.
 * - Vinculación automática del google_id en primer login OAuth.
 * - Rechazo si el email no existe en la BD.
 * - Rechazo si el usuario está desactivado.
 * - No-indexación de las rutas OAuth (X-Robots-Tag).
 */
class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: crear un mock de Socialite que devuelve un usuario de Google simulado.
     */
    private function mockGoogleUser(string $email, string $googleId = '123456789', string $name = 'Test User'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getId')->andReturn($googleId);
        $socialiteUser->shouldReceive('getName')->andReturn($name);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_redirige_a_google_correctamente(): void
    {
        $response = $this->get(route('auth.google.redirect'));

        // Socialite redirige a accounts.google.com
        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_login_exitoso_con_usuario_existente_y_activo(): void
    {
        $user = User::factory()->create([
            'email'     => 'admin@test.com',
            'active'    => true,
            'google_id' => null,
        ]);

        $this->mockGoogleUser('admin@test.com', '999888777');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Verifica que el google_id se ha vinculado
        $user->refresh();
        $this->assertEquals('999888777', $user->google_id);
    }

    public function test_no_sobreescribe_google_id_si_ya_existe(): void
    {
        $user = User::factory()->create([
            'email'     => 'admin@test.com',
            'active'    => true,
            'google_id' => 'original_id',
        ]);

        $this->mockGoogleUser('admin@test.com', 'new_id');

        $this->get(route('auth.google.callback'));

        $user->refresh();
        $this->assertEquals('original_id', $user->google_id);
    }

    public function test_rechaza_email_no_registrado(): void
    {
        $this->mockGoogleUser('noexiste@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_rechaza_usuario_inactivo(): void
    {
        User::factory()->create([
            'email'  => 'inactivo@test.com',
            'active' => false,
        ]);

        $this->mockGoogleUser('inactivo@test.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_rutas_oauth_tienen_header_noindex(): void
    {
        $response = $this->get(route('auth.google.redirect'));

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
