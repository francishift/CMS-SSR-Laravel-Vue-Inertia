<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Controlador de autenticación OAuth con Google.
 *
 * Flujo restringido: solo permite login a usuarios ya registrados en la BD.
 * No se crean cuentas nuevas. Si el email de Google no corresponde a un
 * usuario existente y activo, se redirige al login con un mensaje de error.
 */
class GoogleOAuthController extends Controller
{
    /**
     * Redirigir al usuario a la pantalla de consentimiento de Google.
     */
    public function redirect()
    {
        // Scopes mínimos: solo openid + email.
        // No se solicita 'profile' porque ModSecurity (COMODO WAF regla 210580)
        // interpreta la palabra '.profile' en el parámetro scope del callback
        // como un intento de acceso al archivo del sistema (.profile).
        return Socialite::driver('google')
            ->setScopes(['openid', 'email'])
            ->redirect();
    }

    /**
     * Procesar el callback de Google tras la autorización.
     *
     * Flujo:
     * 1. Obtener datos del usuario de Google.
     * 2. Buscar usuario existente por email en la BD.
     * 3. Si existe y está activo → vincular google_id (si no lo tiene) y autenticar.
     * 4. Si no existe → rechazar con mensaje de error.
     * 5. Si está inactivo → rechazar con mensaje de error.
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            Log::warning('OAuth Google: error al obtener datos del usuario', [
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
                'trace'     => $e->getTraceAsString(),
                'ip'        => $request->ip(),
            ]);

            return redirect()->route('login')->with(
                'error',
                'No se pudo completar la autenticación con Google. Inténtalo de nuevo.'
            );
        }

        // Buscar usuario existente por email (case-insensitive)
        $user = User::where('email', $googleUser->getEmail())->first();

        // Si el email no existe en la BD, rechazar el acceso
        if (! $user) {
            Log::info('OAuth Google: intento de acceso con email no registrado', [
                'email' => $googleUser->getEmail(),
                'ip'    => $request->ip(),
            ]);

            return redirect()->route('login')->with(
                'error',
                'No existe una cuenta asociada a este correo electrónico. Solo los usuarios registrados pueden acceder.'
            );
        }

        // Si el usuario está inactivo, rechazar el acceso
        if (! $user->active) {
            Log::info('OAuth Google: intento de acceso con cuenta desactivada', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $request->ip(),
            ]);

            return redirect()->route('login')->with(
                'error',
                'Tu cuenta está desactivada. Contacta con el administrador.'
            );
        }

        // Vincular el google_id si es la primera vez que el usuario usa OAuth
        if (! $user->google_id) {
            $user->update(['google_id' => $googleUser->getId()]);
        }

        // Autenticar al usuario con "remember" activado
        Auth::login($user, remember: true);

        // Regenerar sesión para prevenir session fixation
        $request->session()->regenerate();

        Log::info('OAuth Google: login exitoso', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }
}
