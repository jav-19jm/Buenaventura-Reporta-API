<?php

use App\Http\Middleware\EnsureUserHasEntity;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Autorización de canales privados de WebSocket con el token JWT: POST /api/broadcasting/auth
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:api', 'activo']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // En Docker la API está detrás del nginx del frontend: se respetan las cabeceras
        // X-Forwarded-* para que las URLs (firmadas, storage) usen el host y esquema públicos.
        $middleware->trustProxies(at: '*');

        // API sin ruta "login": un invitado recibe 401 en JSON (aunque no envíe Accept: application/json)
        // en lugar de un intento de redirección que terminaba en error 500.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');

        $middleware->alias([
            'activo' => EnsureUserIsActive::class,
            'rol' => EnsureUserHasRole::class,
            'con_entidad' => EnsureUserHasEntity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // La API siempre responde JSON, aunque el cliente no envíe "Accept: application/json"
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'No autenticado. Inicia sesión nuevamente.'], 401);
            }
        });
    })->create();
