<?php

namespace App\Services;

use App\Models\Insignia;
use App\Models\User;

/**
 * Otorga automáticamente las insignias que un usuario haya alcanzado.
 * Se ejecuta tras los eventos que cambian sus contadores (crear reporte,
 * recibir votos, reporte resuelto).
 */
class InsigniaService
{
    /**
     * Reglas por nombre de insignia (deben coincidir con InsigniaSeeder).
     *
     * @return array<string, callable(User): bool>
     */
    protected function reglas(): array
    {
        return [
            'Primer Reporte' => fn (User $u) => $u->reportes_creados >= 1,
            '10 Reportes' => fn (User $u) => $u->reportes_creados >= 10,
            '50 Reportes' => fn (User $u) => $u->reportes_creados >= 50,
            'Solucionador' => fn (User $u) => $u->reportes_resueltos >= 1,
            'Embajador' => fn (User $u) => $u->puntuacion_reputacion >= 100,
        ];
    }

    /**
     * @return list<string> Nombres de las insignias otorgadas en esta evaluación
     */
    public function evaluar(User $user): array
    {
        $ganadas = collect($this->reglas())
            ->filter(fn (callable $cumple) => $cumple($user))
            ->keys();

        if ($ganadas->isEmpty()) {
            return [];
        }

        $yaObtenidas = $user->insignias()->pluck('insignias.id');

        $nuevas = Insignia::whereIn('nombre', $ganadas)
            ->whereNotIn('id', $yaObtenidas)
            ->get();

        foreach ($nuevas as $insignia) {
            $user->insignias()->attach($insignia->id, ['fecha_obtencion' => now()]);
        }

        return $nuevas->pluck('nombre')->all();
    }
}
