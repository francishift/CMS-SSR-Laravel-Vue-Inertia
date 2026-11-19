<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Nota: interceptamos el error handler de Laravel para suprimir las
     * advertencias open_basedir que genera laravel/agent-detector al intentar
     * acceder a /opt/.devin en entornos con restricciones (Plesk).
     *
     * El handler de Laravel se captura y se encadena, de modo que el resto
     * de errores siguen siendo gestionados con normalidad.
     */
    public function register(): void
    {
        // Solo actuar si open_basedir está activo (entornos restringidos como Plesk)
        if (ini_get('open_basedir')) {
            // Capturar el handler actual de Laravel
            $laravelHandler = set_error_handler(null);
            restore_error_handler();

            // Registrar nuestro handler por encima — se ejecuta primero
            set_error_handler(
                function (int $errno, string $errstr, string $errfile, int $errline) use ($laravelHandler): bool {
                    // Suprimir solo advertencias de open_basedir
                    if ($errno === E_WARNING && str_contains($errstr, 'open_basedir')) {
                        return true;
                    }
                    // Delegar cualquier otro error al handler de Laravel
                    if (is_callable($laravelHandler)) {
                        return (bool) $laravelHandler($errno, $errstr, $errfile, $errline);
                    }
                    return false;
                }
            );
        }

        // Registrar nuestro SsrGateway personalizado para reservar el SSR únicamente para el frontend público
        $this->app->bind(\Inertia\Ssr\Gateway::class, \App\Services\SsrGateway::class);

        // SeoService como singleton por petición para persistir los nodos Schema.org hacia app.blade.php
        $this->app->singleton(\App\Services\SeoService::class);
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || str_contains(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Reglas de seguridad para contraseñas de usuarios
        \Illuminate\Validation\Rules\Password::defaults(function () {
            $rule = \Illuminate\Validation\Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            return app()->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });

        \Illuminate\Http\Resources\Json\JsonResource::withoutWrapping();

        // Invalidación automática de la caché pública de Redis al modificar cualquier contenido
        \App\Models\Article::observe(\App\Observers\CmsCacheObserver::class);
        \App\Models\Category::observe(\App\Observers\CmsCacheObserver::class);
        \App\Models\Page::observe(\App\Observers\CmsCacheObserver::class);
        \App\Models\Menu::observe(\App\Observers\CmsCacheObserver::class);
        \App\Models\MenuItem::observe(\App\Observers\CmsCacheObserver::class);
        \App\Models\SiteSetting::observe(\App\Observers\CmsCacheObserver::class);
        \App\Models\Form::observe(\App\Observers\CmsCacheObserver::class);

        // Enriquecer cada evento de Spatie Activitylog con IP de origen y registrar accesos de seguridad
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            \Spatie\Activitylog\Models\Activity::saving(function (\Spatie\Activitylog\Models\Activity $activity): void {
                if (app()->runningInConsole() && !app()->runningUnitTests()) {
                    return;
                }
                $req = request();
                if (!$req) {
                    return;
                }
                $props = $activity->properties ? $activity->properties->toArray() : [];
                if (empty($props['ip']) && $req->ip()) {
                    $props['ip'] = $req->ip();
                }
                if (empty($props['user_agent']) && $req->userAgent()) {
                    $props['user_agent'] = mb_substr((string) $req->userAgent(), 0, 255);
                }
                $activity->properties = collect($props);
            });

            \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event): void {
                if ($event->user instanceof \Illuminate\Database\Eloquent\Model) {
                    activity('seguridad')
                        ->causedBy($event->user)
                        ->performedOn($event->user)
                        ->event('login')
                        ->log('Inicio de sesión en el panel (' . ($event->user->email ?? $event->user->getKey()) . ')');
                }
            });

            \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function (\Illuminate\Auth\Events\Failed $event): void {
                $email = (string) ($event->credentials['email'] ?? 'desconocido');
                activity('seguridad')
                    ->event('login_failed')
                    ->withProperties(['email' => mb_substr($email, 0, 180)])
                    ->log("Intento fallido de inicio de sesión ({$email})");
            });
        }
    }
}
