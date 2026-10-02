<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Datos de un usuario visibles para cualquiera (sin correo ni teléfono).
 *
 * @mixin User
 */
class PerfilPublicoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_completo' => $this->nombre_completo,
            'url_avatar' => $this->url_avatar,
            'rol' => $this->rol,
            'puntuacion_reputacion' => $this->puntuacion_reputacion,
            'votos_positivos' => $this->votos_positivos,
            'votos_negativos' => $this->votos_negativos,
            'reportes_creados' => $this->reportes_creados,
            'reportes_resueltos' => $this->reportes_resueltos,
            'fecha_creacion' => $this->fecha_creacion,
        ];
    }
}
