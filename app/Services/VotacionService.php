<?php

namespace App\Services;

use App\Enums\TipoNotificacion;
use App\Enums\TipoVoto;
use App\Models\Reporte;
use App\Models\User;
use App\Models\VotoReporte;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Votos ciudadanos sobre los reportes y reputación del autor.
 * Reputación del autor = votos positivos - votos negativos de todos sus reportes.
 */
class VotacionService
{
    public function __construct(
        private InsigniaService $insignias,
        private Notificador $notificador,
    ) {}

    public function votar(Reporte $reporte, User $votante, TipoVoto $tipo): Reporte
    {
        DB::transaction(function () use ($reporte, $votante, $tipo) {
            $voto = VotoReporte::where('id_reporte', $reporte->id)
                ->where('id_usuario', $votante->id)
                ->lockForUpdate()
                ->first();

            if ($voto?->tipo_voto === $tipo) {
                throw ValidationException::withMessages(['tipo_voto' => 'Ya has votado lo mismo en este reporte.']);
            }

            if ($voto) {
                $voto->update(['tipo_voto' => $tipo]);
            } else {
                VotoReporte::create([
                    'id_reporte' => $reporte->id,
                    'id_usuario' => $votante->id,
                    'tipo_voto' => $tipo,
                ]);
            }

            $this->recalcularReporte($reporte);
            $this->recalcularAutor($reporte->usuario);
        });

        $autor = $reporte->usuario->fresh();
        $this->insignias->evaluar($autor);

        if ($autor->id !== $votante->id) {
            $etiqueta = $tipo === TipoVoto::Positivo ? 'un voto positivo' : 'un voto negativo';
            $this->notificador->notificar(
                [$autor->id],
                TipoNotificacion::Mencion,
                'Nuevo voto en tu reporte',
                "Alguien ha dado {$etiqueta} a tu reporte.",
                $reporte,
            );
        }

        return $reporte->refresh();
    }

    private function recalcularReporte(Reporte $reporte): void
    {
        $reporte->forceFill([
            'votos_positivos' => $reporte->votos()->where('tipo_voto', TipoVoto::Positivo)->count(),
            'votos_negativos' => $reporte->votos()->where('tipo_voto', TipoVoto::Negativo)->count(),
        ])->save();
    }

    private function recalcularAutor(User $autor): void
    {
        $positivos = (int) $autor->reportes()->sum('votos_positivos');
        $negativos = (int) $autor->reportes()->sum('votos_negativos');

        $autor->forceFill([
            'votos_positivos' => $positivos,
            'votos_negativos' => $negativos,
            'puntuacion_reputacion' => $positivos - $negativos,
        ])->save();
    }
}
