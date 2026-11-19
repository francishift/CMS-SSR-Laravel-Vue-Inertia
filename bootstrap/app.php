<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Confiar únicamente en el proxy inverso local (Nginx -> Apache en el propio servidor)
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '::1',
        ]);

        $middleware->prepend(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->web(prepend: [
            \App\Http\Middleware\PublicResponseCache::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\VerificarUsuarioActivo::class,
        ]);

        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, Request $request) {
            if ($request->is('api/*')) {
                return $response;
            }

            if (in_array($response->getStatusCode(), [404, 403, 500, 503])) {
                $errorResponse = \Inertia\Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());

                return \App\Http\Middleware\SecurityHeaders::applyToResponse($request, $errorResponse);
            }

            return \App\Http\Middleware\SecurityHeaders::applyToResponse($request, $response);
        });
    })->create();
