<?php

namespace App\Enums;

enum TipoRemitente: string
{
    case Usuario = 'usuario';
    case Entidad = 'entidad';
    case Moderador = 'moderador';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
