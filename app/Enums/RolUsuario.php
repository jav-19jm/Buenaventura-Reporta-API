<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Ciudadano = 'ciudadano';
    case Entidad = 'entidad';
    case Moderador = 'moderador';
    case Administrador = 'administrador';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
