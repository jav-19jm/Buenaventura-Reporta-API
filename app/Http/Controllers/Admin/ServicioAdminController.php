<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoNotificacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServicioRequest;
use App\Models\Servicio;
use App\Services\Notificador;
use Illuminate\Http\JsonResponse;

/**
 * Servicios de la ciudad (puntos de interés del mapa).
 */
class ServicioAdminController extends Controller
{
    /** Todos los servicios, incluidos los inactivos */
    public function index(): JsonResponse
    {
        return response()->json(Servicio::orderBy('nombre')->get());
    }

    public function store(ServicioRequest $request, Notificador $notificador): JsonResponse
    {
        $servicio = Servicio::create($request->validated());

        if ($servicio->esta_activo) {
            $notificador->notificar(
                $notificador->idsCiudadanos(),
                TipoNotificacion::AlertaSistema,
                'Nuevo servicio disponible',
                "Se ha registrado un punto de servicio: {$servicio->nombre}. ¡Ya puedes consultarlo en el mapa de servicios!",
            );
        }

        return response()->json($servicio, 201);
    }

    public function update(ServicioRequest $request, Servicio $servicio): JsonResponse
    {
        $servicio->update($request->validated());

        return response()->json($servicio);
    }

    public function destroy(Servicio $servicio): JsonResponse
    {
        $servicio->delete();

        return response()->json(['message' => 'Servicio eliminado.']);
    }
}
