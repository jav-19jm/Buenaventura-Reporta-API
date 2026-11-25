<?php

namespace App\Models;

use App\Enums\TipoVoto;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VotoReporte extends Model
{
    use HasUuids;

    protected $table = 'votos_reportes';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    protected $fillable = ['id_reporte', 'id_usuario', 'tipo_voto'];

    protected function casts(): array
    {
        return ['tipo_voto' => TipoVoto::class];
    }

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class, 'id_reporte');
    }
}
