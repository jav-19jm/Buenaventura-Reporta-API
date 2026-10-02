<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivote users <-> insignias (tabla insignias_usuarios), con id UUID propio.
 */
class InsigniaUsuario extends Pivot
{
    use HasUuids;

    protected $table = 'insignias_usuarios';

    public $incrementing = false;

    protected $keyType = 'string';

    /** fecha_obtencion se asigna al otorgar la insignia (InsigniaService) */
    public $timestamps = false;
}
