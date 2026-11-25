<?php

namespace App\Http\Resources;

use App\Models\Noticia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Noticia
 */
class NoticiaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->attributesToArray(),
            'entidades' => $this->whenLoaded('entidad', fn () => $this->entidad?->only([
                'id', 'nombre', 'slug', 'color', 'logo_url',
            ])),
        ];
    }
}
