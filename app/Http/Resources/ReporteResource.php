<?php

namespace App\Http\Resources;

use App\Models\Reporte;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reporte con las relaciones con los mismos nombres que usaba Supabase:
 * "perfiles" (autor) y "entidades" (entidad asignada).
 *
 * @mixin Reporte
 */
class ReporteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->attributesToArray(),
            'perfiles' => $this->whenLoaded('usuario', fn () => $this->usuario ? [
                'id' => $this->usuario->id,
                'nombre_completo' => $this->usuario->nombre_completo,
                'url_avatar' => $this->usuario->url_avatar,
                'puntuacion_reputacion' => $this->usuario->puntuacion_reputacion,
            ] : null),
            'entidades' => $this->whenLoaded('entidad', fn () => $this->entidad?->only([
                'id', 'nombre', 'slug', 'color', 'logo_url', 'email', 'telefono', 'descripcion', 'sitio_web',
            ])),
        ];
    }
}
