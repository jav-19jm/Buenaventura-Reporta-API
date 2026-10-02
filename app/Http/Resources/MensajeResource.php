<?php

namespace App\Http\Resources;

use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Mensaje
 */
class MensajeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->attributesToArray(),
            'perfiles' => $this->whenLoaded('remitente', fn () => $this->remitente?->only([
                'nombre_completo', 'url_avatar', 'rol',
            ])),
        ];
    }
}
