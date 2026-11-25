<?php

namespace App\Models;

use App\Enums\TipoEntidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entidad extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'entidades';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_actualizacion';

    protected $attributes = ['esta_activa' => true];

    protected $fillable = [
        'nombre', 'slug', 'descripcion', 'tipo', 'email', 'telefono',
        'color', 'logo_url', 'sitio_web', 'esta_activa',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoEntidad::class,
            'esta_activa' => 'boolean',
        ];
    }

    /** @param  Builder<Entidad>  $query */
    public function scopeActivas(Builder $query): void
    {
        $query->where('esta_activa', true);
    }

    /** Cuentas de usuario (rol "entidad") que gestionan esta entidad */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'id_entidad');
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'id_entidad');
    }
}
