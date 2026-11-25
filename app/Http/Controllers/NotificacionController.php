<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /** Últimas notificaciones del usuario autenticado */
    public function index(Request $request): JsonResponse
    {
        $notificaciones = $request->user()->notificaciones()
            ->latest('fecha_creacion')
            ->limit(100)
            ->get();

        return response()->json($notificaciones);
    }

    public function marcarLeida(Request $request, Notificacion $notificacion): JsonResponse
    {
        $this->asegurarPropietario($request, $notificacion);

        $notificacion->update(['esta_leida' => true]);

        return response()->json($notificacion);
    }

    public function destroy(Request $request, Notificacion $notificacion): JsonResponse
    {
        $this->asegurarPropietario($request, $notificacion);

        $notificacion->delete();

        return response()->json(['message' => 'Notificación eliminada.']);
    }

    /** Las notificaciones ajenas se tratan como inexistentes */
    private function asegurarPropietario(Request $request, Notificacion $notificacion): void
    {
        abort_if($notificacion->id_usuario !== $request->user()->id, 404, 'La notificación no existe.');
    }
}
