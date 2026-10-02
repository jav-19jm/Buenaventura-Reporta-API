<?php

namespace App\Services;

use App\Enums\RolUsuario;
use App\Enums\TipoNotificacion;
use App\Models\Notificacion;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Crea las notificaciones internas (campana del panel).
 */
class Notificador
{
    /**
     * @param  iterable<string>  $idsUsuarios
     */
    public function notificar(iterable $idsUsuarios, TipoNotificacion $tipo, string $titulo, string $mensaje, ?Reporte $reporte = null): void
    {
        $filas = collect($idsUsuarios)->unique()->map(fn (string $id) => [
            'id_usuario' => $id,
            'id_reporte' => $reporte?->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
        ]);

        // create() por fila para generar el UUID y la fecha de creación
        $filas->each(fn (array $fila) => Notificacion::create($fila));
    }

    /** @return Collection<int, string> */
    public function idsAdministradores(?User $excepto = null): Collection
    {
        return User::where('rol', RolUsuario::Administrador)
            ->when($excepto, fn ($q) => $q->whereKeyNot($excepto->id))
            ->pluck('id');
    }

    /** @return Collection<int, string> Cuentas que gestionan la entidad asignada al reporte */
    public function idsEntidad(Reporte $reporte, ?User $excepto = null): Collection
    {
        if (! $reporte->id_entidad) {
            return collect();
        }

        return User::where('id_entidad', $reporte->id_entidad)
            ->when($excepto, fn ($q) => $q->whereKeyNot($excepto->id))
            ->pluck('id');
    }
}
