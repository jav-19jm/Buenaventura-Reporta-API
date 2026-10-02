<?php

namespace App\Models;

use App\Enums\EstadoReporte;
use App\Enums\PrioridadReporte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reporte extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reportes';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_actualizacion';

    protected $attributes = [
        'estado' => 'pendiente',
        'prioridad' => 'media',
        'votos_positivos' => 0,
        'votos_negativos' => 0,
        'visto' => false,
        'visible' => true,
    ];

    /** id_usuario, estado, votos y visibilidad los asigna el backend, no el cliente */
    protected $fillable = [
        'titulo', 'descripcion', 'categoria', 'direccion_ubicacion',
        'latitud', 'longitud', 'prioridad', 'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoReporte::class,
            'prioridad' => PrioridadReporte::class,
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'votos_positivos' => 'integer',
            'votos_negativos' => 'integer',
            'visto' => 'boolean',
            'visible' => 'boolean',
        ];
    }

    /** @param  Builder<Reporte>  $query */
    public function scopeVisibles(Builder $query): void
    {
        $query->where('visible', true);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function votos(): HasMany
    {
        return $this->hasMany(VotoReporte::class, 'id_reporte');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'id_reporte');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialReporte::class, 'id_reporte');
    }

    public function registrarHistorial(string $accion, ?User $usuario, ?string $anterior = null, ?string $nuevo = null): void
    {
        $this->historial()->create([
            'accion' => $accion,
            'valor_anterior' => $anterior,
            'valor_nuevo' => $nuevo,
            'id_usuario' => $usuario?->id,
        ]);
    }
}
