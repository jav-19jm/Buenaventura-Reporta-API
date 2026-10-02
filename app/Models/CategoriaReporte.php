<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoriaReporte extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'categorias_reportes';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    protected $attributes = ['esta_activa' => true];

    protected $fillable = ['id_entidad', 'nombre', 'icono', 'color', 'descripcion', 'esta_activa'];

    protected function casts(): array
    {
        return ['esta_activa' => 'boolean'];
    }

    /** @param  Builder<CategoriaReporte>  $query */
    public function scopeActivas(Builder $query): void
    {
        $query->where('esta_activa', true);
    }

    /** Entidad responsable por defecto de los reportes de esta categoría */
    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
