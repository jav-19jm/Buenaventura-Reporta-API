<?php

namespace App\Enums;

enum EstadoUsuario: string
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';
    case Suspendido = 'suspendido';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
