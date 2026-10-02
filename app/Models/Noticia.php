<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Noticia extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'noticias';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_actualizacion';

    protected $attributes = ['esta_publicada' => false];

    protected $fillable = [
        'id_entidad', 'titulo', 'contenido', 'url_imagen', 'categoria',
        'esta_publicada', 'fecha_publicacion',
    ];

    protected function casts(): array
    {
        return [
            'esta_publicada' => 'boolean',
            'fecha_publicacion' => 'datetime',
        ];
    }

    /** @param  Builder<Noticia>  $query */
    public function scopePublicadas(Builder $query): void
    {
        $query->where('esta_publicada', true);
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
