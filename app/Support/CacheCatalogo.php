<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Caché (Redis en Docker) de los catálogos públicos que se consultan en cada
 * carga del mapa y de los formularios. Los modelos la invalidan al cambiar.
 */
class CacheCatalogo
{
    const CATEGORIAS = 'catalogo.categorias';

    const ENTIDADES = 'catalogo.entidades';

    const SERVICIOS = 'catalogo.servicios';

    const NOTICIAS = 'catalogo.noticias';

    private const MINUTOS = 10;

    /**
     * @template T
     *
     * @param  Closure(): T  $consulta
     * @return T
     */
    public static function recordar(string $clave, Closure $consulta): mixed
    {
        return Cache::remember($clave, now()->addMinutes(self::MINUTOS), $consulta);
    }

    public static function olvidar(string ...$claves): void
    {
        foreach ($claves as $clave) {
            Cache::forget($clave);
        }
    }

    /**
     * Registra en un modelo la invalidación de las claves indicadas al guardar o eliminar.
     *
     * @param  class-string<Model>  $modelo
     */
    public static function invalidarAlCambiar(string $modelo, string ...$claves): void
    {
        $modelo::saved(fn () => self::olvidar(...$claves));
        $modelo::deleted(fn () => self::olvidar(...$claves));
    }
}
