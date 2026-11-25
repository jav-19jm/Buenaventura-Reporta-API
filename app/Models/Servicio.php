<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Servicio de la ciudad (punto de interés en el mapa: hospitales, estaciones...).
 */
class Servicio extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'servicios';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_actualizacion';

    protected $attributes = ['esta_activo' => true];

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'latitud', 'longitud',
        'direccion', 'horario', 'telefono', 'esta_activo',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'esta_activo' => 'boolean',
        ];
    }

    /** @param  Builder<Servicio>  $query */
    public function scopeActivos(Builder $query): void
    {
        $query->where('esta_activo', true);
    }
}
