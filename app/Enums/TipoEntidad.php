<?php

namespace App\Enums;

enum TipoEntidad: string
{
    case ServiciosPublicos = 'servicios-publicos';
    case Seguridad = 'seguridad';
    case Salud = 'salud';
    case Infraestructura = 'infraestructura';
    case Ambiente = 'ambiente';
    case Otro = 'otro';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
