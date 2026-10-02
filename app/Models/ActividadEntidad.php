<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ActividadEntidad extends Model
{
    use HasUuids;

    protected $table = 'actividad_entidades';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    /** Tipos que distingue la UI con iconos propios */
    const AUTH = 'auth';

    const ACTUALIZACION = 'update';

    const REPORTE = 'reporte';

    protected $fillable = ['id_entidad', 'tipo_accion', 'titulo', 'descripcion'];

    /** Registra una acción en la auditoría de la entidad */
    public static function registrar(string $idEntidad, string $tipo, string $titulo, string $descripcion): self
    {
        return self::create([
            'id_entidad' => $idEntidad,
            'tipo_accion' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
        ]);
    }
}
