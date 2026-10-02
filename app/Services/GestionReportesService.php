<?php

namespace App\Services;

use App\Enums\EstadoReporte;
use App\Enums\TipoNotificacion;
use App\Models\Entidad;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Acciones institucionales sobre un reporte: cambio de estado y asignación
 * de entidad. Las usan la administración y (más adelante) el panel de entidad.
 */
class GestionReportesService
{
    private const ETIQUETAS_ESTADO = [
        'pendiente' => 'pendiente',
        'en_revision' => 'en revisión',
        'en_proceso' => 'en proceso',
        'resuelto' => 'resuelto',
        'cancelado' => 'cancelado',
    ];

    public function __construct(
        private Notificador $notificador,
        private InsigniaService $insignias,
    ) {}

    /**
     * Cambia el estado, lo registra en el historial, mantiene el contador
     * reportes_resueltos del autor y le notifica el cambio.
     */
    public function cambiarEstado(Reporte $reporte, EstadoReporte $nuevo, User $responsable): Reporte
    {
        $anterior = $reporte->estado;

        if ($anterior === $nuevo) {
            return $reporte;
        }

        DB::transaction(function () use ($reporte, $anterior, $nuevo, $responsable) {
            $reporte->forceFill(['estado' => $nuevo, 'visto' => true])->save();
            $reporte->registrarHistorial('cambio_estado', $responsable, $anterior->value, $nuevo->value);

            $autor = $reporte->usuario;
            if ($nuevo === EstadoReporte::Resuelto) {
                $autor->increment('reportes_resueltos');
            } elseif ($anterior === EstadoReporte::Resuelto && $autor->reportes_resueltos > 0) {
                $autor->decrement('reportes_resueltos');
            }
        });

        if ($nuevo === EstadoReporte::Resuelto) {
            $this->insignias->evaluar($reporte->usuario->fresh());
        }

        $this->notificador->notificar(
            [$reporte->id_usuario],
            $nuevo === EstadoReporte::Resuelto ? TipoNotificacion::ReporteResuelto : TipoNotificacion::ReporteActualizado,
            'Estado de reporte actualizado',
            "Tu reporte \"{$reporte->titulo}\" cambió a: ".self::ETIQUETAS_ESTADO[$nuevo->value].'.',
            $reporte,
        );

        return $reporte->refresh();
    }

    /**
     * Asigna el reporte a una entidad y avisa al autor y a las cuentas de la entidad.
     */
    public function asignarEntidad(Reporte $reporte, Entidad $entidad, User $responsable): Reporte
    {
        $anterior = $reporte->id_entidad;

        if ($anterior === $entidad->id) {
            return $reporte->load('entidad');
        }

        $reporte->forceFill(['id_entidad' => $entidad->id])->save();
        $reporte->registrarHistorial('asignacion_entidad', $responsable, $anterior, $entidad->id);

        $this->notificador->notificar(
            [$reporte->id_usuario],
            TipoNotificacion::ReporteActualizado,
            'Reporte asignado',
            "Tu reporte \"{$reporte->titulo}\" ha sido asignado a: {$entidad->nombre}.",
            $reporte,
        );

        $this->notificador->notificar(
            $this->notificador->idsEntidad($reporte),
            TipoNotificacion::ReporteActualizado,
            'Nuevo reporte asignado',
            "Se ha asignado un nuevo reporte a tu entidad: {$reporte->titulo}",
            $reporte,
        );

        return $reporte->refresh()->load('entidad');
    }
}
