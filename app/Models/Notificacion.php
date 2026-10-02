<?php

namespace App\Models;

use App\Enums\TipoNotificacion;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasUuids;

    protected $table = 'notificaciones';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    protected $attributes = ['esta_leida' => false];

    protected $fillable = ['id_usuario', 'id_reporte', 'tipo', 'titulo', 'mensaje', 'esta_leida'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoNotificacion::class,
            'esta_leida' => 'boolean',
        ];
    }
}
