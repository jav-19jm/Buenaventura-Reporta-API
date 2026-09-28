<?php

namespace App\Enums;

enum TipoNotificacion: string
{
    case ReporteActualizado = 'reporte_actualizado';
    case NuevoMensaje = 'nuevo_mensaje';
    case ReporteResuelto = 'reporte_resuelto';
    case Mencion = 'mencion';
    case AlertaSistema = 'alerta_sistema';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
