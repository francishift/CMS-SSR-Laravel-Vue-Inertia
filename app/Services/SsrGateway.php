<?php

namespace App\Services;

use Inertia\Ssr\HttpGateway;
use Inertia\Ssr\Response;

/**
 * Gateway de SSR personalizado.
 * 
 * Se encarga de omitir el renderizado SSR (Server-Side Rendering)
 * para todas las rutas privadas del backend y de autenticación,
 * reservando el SSR exclusivamente para las páginas web públicas.
 */
class SsrGateway extends HttpGateway
{
    /**
     * Enviar la página al motor de SSR únicamente si es una ruta pública.
     */
    public function dispatch(array $page): ?Response
    {
        // Rutas del backend o autenticación: omitir SSR y renderizar solo en cliente
        if (request()->is(
            'panel*',
            'acceso*',
            'login*',
            'register*',
            'forgot-password*',
            'reset-password*',
            'confirm-password*',
            'password*',
            'logout*',
            'verify-email*'
        )) {
            return null;
        }

        return parent::dispatch($page);
    }
}
