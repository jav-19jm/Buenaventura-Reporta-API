<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HistorialReporte extends Model
{
    use HasUuids;

    protected $table = 'historial_reportes';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    protected $fillable = ['id_reporte', 'accion', 'valor_anterior', 'valor_nuevo', 'id_usuario'];
}
