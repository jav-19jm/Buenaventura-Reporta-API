<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel institucional: la cuenta debe tener rol "entidad" y estar vinculada
 * a una entidad existente (users.id_entidad).
 */
class EnsureUserHasEntity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tieneRol('entidad')) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        if (! $user->entidad) {
            return response()->json([
                'message' => 'Tu cuenta no está vinculada a ninguna entidad. Contacta a la administración.',
                'codigo' => 'sin_entidad',
            ], 403);
        }

        return $next($request);
    }
}
