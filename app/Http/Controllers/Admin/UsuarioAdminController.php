<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioAdminController extends Controller
{
    /** Todos los usuarios, los más recientes primero */
    public function index(): JsonResponse
    {
        return response()->json(User::latest('fecha_creacion')->get());
    }

    /**
     * Activa, inactiva o suspende una cuenta. El bloqueo aplica de inmediato:
     * el middleware "activo" rechaza sus tokens vigentes.
     */
    public function cambiarEstado(Request $request, User $usuario): JsonResponse
    {
        $validated = $request->validate([
            'estado' => ['required', Rule::enum(EstadoUsuario::class)],
            'motivo_bloqueo' => ['nullable', 'string', 'max:500'],
        ]);

        $this->impedirSobreSiMismo($request, $usuario, 'cambiar el estado de tu propia cuenta');

        $estado = EstadoUsuario::from($validated['estado']);
        $usuario->forceFill([
            'estado' => $estado,
            'motivo_bloqueo' => $estado === EstadoUsuario::Activo ? null : ($validated['motivo_bloqueo'] ?? null),
        ])->save();

        return response()->json($usuario);
    }

    /**
     * Cambia el rol. Las cuentas de entidad se gestionan desde la pestaña de
     * entidades, por eso aquí no se asigna ni se quita el rol "entidad".
     */
    public function cambiarRol(Request $request, User $usuario): JsonResponse
    {
        $validated = $request->validate([
            'rol' => ['required', Rule::in([RolUsuario::Ciudadano->value, RolUsuario::Moderador->value, RolUsuario::Administrador->value])],
        ], [
            'rol.in' => 'El rol de entidad se asigna desde la gestión de entidades.',
        ]);

        $this->impedirSobreSiMismo($request, $usuario, 'cambiar tu propio rol');
        abort_if($usuario->tieneRol(RolUsuario::Entidad), 422, 'Las cuentas de entidad se gestionan desde la gestión de entidades.');

        $usuario->forceFill(['rol' => $validated['rol']])->save();

        return response()->json($usuario);
    }

    private function impedirSobreSiMismo(Request $request, User $usuario, string $accion): void
    {
        abort_if($request->user()->id === $usuario->id, 422, "No puedes {$accion}.");
    }
}
