<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rechaza las peticiones de cuentas suspendidas o inactivas aunque tengan un
 * token vigente (el bloqueo aplica de inmediato, sin esperar a que expire).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->estaActivo()) {
            auth('api')->invalidate();

            return AuthController::respuestaCuentaInactiva($user);
        }

        return $next($request);
    }
}
