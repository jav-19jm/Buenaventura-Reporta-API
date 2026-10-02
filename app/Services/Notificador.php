<?php

namespace App\Services;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Enums\TipoNotificacion;
use App\Models\Notificacion;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $ahora = now();

        // Inserción masiva por bloques: los avisos generales llegan a todos los ciudadanos
        collect($idsUsuarios)->unique()->values()
            ->map(fn (string $id) => [
                'id' => (string) Str::uuid7(),
                'id_usuario' => $id,
                'id_reporte' => $reporte?->id,
                'tipo' => $tipo->value,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'esta_leida' => false,
                'fecha_creacion' => $ahora,
            ])
            ->chunk(500)
            ->each(fn (Collection $bloque) => Notificacion::insert($bloque->all()));
    }

    /** @return Collection<int, string> */
    public function idsCiudadanos(): Collection
    {
        return User::where('rol', RolUsuario::Ciudadano)
            ->where('estado', EstadoUsuario::Activo)
            ->pluck('id');
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
            ->where('rol', RolUsuario::Entidad)
            ->when($excepto, fn ($q) => $q->whereKeyNot($excepto->id))
            ->pluck('id');
    }
}
