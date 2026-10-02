<?php

namespace App\Models;

use App\Enums\TipoRemitente;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensaje extends Model
{
    use HasUuids;

    protected $table = 'mensajes';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    /** Minutos durante los que el autor puede editar o eliminar su mensaje */
    const MINUTOS_EDICION = 5;

    protected $fillable = ['id_reporte', 'id_remitente', 'tipo_remitente', 'mensaje'];

    protected function casts(): array
    {
        return ['tipo_remitente' => TipoRemitente::class];
    }

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class, 'id_reporte');
    }

    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_remitente');
    }

    public function editablePor(User $user): bool
    {
        return $this->id_remitente === $user->id
            && $this->fecha_creacion->gt(now()->subMinutes(self::MINUTOS_EDICION));
    }
}
