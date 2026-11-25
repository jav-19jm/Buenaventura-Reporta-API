<?php

namespace App\Enums;

enum TipoVoto: string
{
    case Positivo = 'voto_positivo';
    case Negativo = 'voto_negativo';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
