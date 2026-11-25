<?php

namespace App\Enums;

enum EstadoReporte: string
{
    case Pendiente = 'pendiente';
    case EnRevision = 'en_revision';
    case EnProceso = 'en_proceso';
    case Resuelto = 'resuelto';
    case Cancelado = 'cancelado';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
