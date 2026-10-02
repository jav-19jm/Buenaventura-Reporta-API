<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EntidadRequest;
use App\Models\Entidad;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Entidades institucionales y su cuenta de acceso (rol "entidad").
 */
class EntidadAdminController extends Controller
{
    /** Todas las entidades, incluidas las inactivas */
    public function index(): JsonResponse
    {
        return response()->json(Entidad::orderBy('nombre')->get());
    }

    /** Crea la entidad y su cuenta institucional en una sola transacción */
    public function store(EntidadRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $entidad = DB::transaction(function () use ($datos) {
            $entidad = Entidad::create(Arr::except($datos, ['password']));

            $cuenta = new User([
                'nombre_completo' => $entidad->nombre,
                'email' => $datos['email'],
                'password' => $datos['password'],
                'telefono' => $entidad->telefono,
            ]);
            $cuenta->forceFill([
                'rol' => RolUsuario::Entidad,
                'id_entidad' => $entidad->id,
                // La crea la administración: no requiere confirmar el correo
                'email_verified_at' => now(),
            ])->save();

            return $entidad;
        });

        return response()->json($entidad, 201);
    }

    /**
     * Actualiza la entidad y sincroniza nombre, correo y (si llega) la
     * contraseña de su cuenta institucional principal.
     */
    public function update(EntidadRequest $request, Entidad $entidad): JsonResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($entidad, $datos) {
            $entidad->update(Arr::except($datos, ['password']));

            $cuenta = $entidad->usuarios()->oldest('fecha_creacion')->first();
            if (! $cuenta) {
                return;
            }

            $cuenta->nombre_completo = $entidad->nombre;
            if (! empty($datos['email'])) {
                $cuenta->email = $datos['email'];
            }
            if (! empty($datos['password'])) {
                $cuenta->password = $datos['password'];
            }
            $cuenta->save();
        });

        return response()->json($entidad->refresh());
    }

    /**
     * Elimina la entidad. Sus reportes quedan sin asignar y sus cuentas se
     * desactivan para que no puedan seguir entrando al panel institucional.
     */
    public function destroy(Entidad $entidad): JsonResponse
    {
        DB::transaction(function () use ($entidad) {
            $entidad->usuarios()->update([
                'estado' => EstadoUsuario::Inactivo->value,
                'motivo_bloqueo' => "La entidad {$entidad->nombre} fue eliminada.",
            ]);

            $entidad->delete();
        });

        return response()->json(['message' => 'Entidad eliminada.']);
    }
}
