<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Enums\TipoNotificacion;
use App\Enums\TipoRemitente;
use App\Http\Resources\MensajeResource;
use App\Models\Mensaje;
use App\Models\Reporte;
use App\Models\User;
use App\Services\Notificador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Chat de seguimiento de un reporte entre el ciudadano, la entidad y la administración.
 */
class MensajeController extends Controller
{
    public function __construct(private Notificador $notificador) {}

    public function index(Reporte $reporte): AnonymousResourceCollection
    {
        Gate::authorize('participarEnChat', $reporte);

        $mensajes = $reporte->mensajes()
            ->with('remitente')
            ->orderBy('fecha_creacion')
            ->get();

        return MensajeResource::collection($mensajes);
    }

    public function store(Request $request, Reporte $reporte): JsonResponse
    {
        Gate::authorize('participarEnChat', $reporte);

        $validated = $request->validate([
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $tipo = $this->tipoRemitente($user);

        $mensaje = $reporte->mensajes()->create([
            'id_remitente' => $user->id,
            'tipo_remitente' => $tipo,
            'mensaje' => $validated['mensaje'],
        ]);

        $this->notificarParticipantes($reporte, $user, $tipo);

        return (new MensajeResource($mensaje->load('remitente')))->response()->setStatusCode(201);
    }

    /** Solo el autor y durante los primeros minutos */
    public function update(Request $request, Mensaje $mensaje): MensajeResource
    {
        $this->asegurarEditable($request->user(), $mensaje, 'editar');

        $validated = $request->validate([
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        $mensaje->update($validated);

        return new MensajeResource($mensaje->load('remitente'));
    }

    public function destroy(Request $request, Mensaje $mensaje): JsonResponse
    {
        $this->asegurarEditable($request->user(), $mensaje, 'eliminar');

        $mensaje->delete();

        return response()->json(['message' => 'Mensaje eliminado.']);
    }

    private function asegurarEditable(User $user, Mensaje $mensaje, string $accion): void
    {
        abort_if($mensaje->id_remitente !== $user->id, 403, "No puedes {$accion} este mensaje.");

        abort_unless(
            $mensaje->editablePor($user),
            403,
            'El tiempo límite de '.Mensaje::MINUTOS_EDICION." minutos para {$accion} ha expirado."
        );
    }

    private function tipoRemitente(User $user): TipoRemitente
    {
        return match (true) {
            $user->tieneRol(RolUsuario::Administrador, RolUsuario::Moderador) => TipoRemitente::Moderador,
            $user->tieneRol(RolUsuario::Entidad) => TipoRemitente::Entidad,
            default => TipoRemitente::Usuario,
        };
    }

    /**
     * Avisa del nuevo mensaje a los demás participantes: administradores,
     * autor del reporte y cuentas de la entidad asignada.
     */
    private function notificarParticipantes(Reporte $reporte, User $remitente, TipoRemitente $tipo): void
    {
        $origen = match ($tipo) {
            TipoRemitente::Usuario => 'ciudadano',
            TipoRemitente::Entidad => 'entidad',
            TipoRemitente::Moderador => 'administración',
        };

        $this->notificador->notificar(
            $this->notificador->idsAdministradores(excepto: $remitente),
            TipoNotificacion::NuevoMensaje,
            'Actividad en reporte',
            "Nuevo mensaje de {$origen} en: {$reporte->titulo}",
            $reporte,
        );

        if ($reporte->id_usuario !== $remitente->id) {
            $quien = $tipo === TipoRemitente::Moderador ? 'La Administración' : 'La Entidad Responsable';
            $this->notificador->notificar(
                [$reporte->id_usuario],
                TipoNotificacion::NuevoMensaje,
                'Nueva respuesta institucional',
                "{$quien} ha respondido a tu reporte: {$reporte->titulo}",
                $reporte,
            );
        }

        if ($tipo !== TipoRemitente::Entidad) {
            $this->notificador->notificar(
                $this->notificador->idsEntidad($reporte, excepto: $remitente),
                TipoNotificacion::NuevoMensaje,
                'Nuevo mensaje en reporte asignado',
                "Hay nueva actividad en el reporte: {$reporte->titulo}",
                $reporte,
            );
        }
    }
}
