<?php

namespace App\Http\Resources;

use App\Models\Insignia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Insignia obtenida por un usuario (cargada mediante User::insignias()).
 *
 * @mixin Insignia
 */
class InsigniaObtenidaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'icono' => $this->icono,
            'requisito_texto' => $this->requisito_texto,
            'fecha_obtencion' => $this->pivot?->fecha_obtencion,
        ];
    }
}
