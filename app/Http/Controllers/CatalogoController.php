<?php

namespace App\Http\Controllers;

use App\Http\Resources\NoticiaResource;
use App\Models\CategoriaReporte;
use App\Models\Entidad;
use App\Models\Noticia;
use App\Models\Servicio;
use App\Support\CacheCatalogo;
use Illuminate\Http\JsonResponse;

/**
 * Catálogos de lectura pública que alimentan el mapa y los formularios.
 * Se sirven desde caché; los modelos la invalidan al modificarse.
 */
class CatalogoController extends Controller
{
    /** Categorías de incidencia disponibles para reportar */
    public function categorias(): JsonResponse
    {
        return response()->json(CacheCatalogo::recordar(CacheCatalogo::CATEGORIAS,
            fn () => CategoriaReporte::activas()->orderBy('nombre')->get()->toArray()));
    }

    /** Entidades activas (selector de entidad responsable) */
    public function entidades(): JsonResponse
    {
        return response()->json(CacheCatalogo::recordar(CacheCatalogo::ENTIDADES,
            fn () => Entidad::activas()->orderBy('nombre')->get()->toArray()));
    }

    /** Servicios de la ciudad que se muestran en el mapa */
    public function servicios(): JsonResponse
    {
        return response()->json(CacheCatalogo::recordar(CacheCatalogo::SERVICIOS,
            fn () => Servicio::activos()->orderBy('nombre')->get()->toArray()));
    }

    /** Noticias publicadas, las más recientes primero */
    public function noticias(): JsonResponse
    {
        return response()->json(CacheCatalogo::recordar(CacheCatalogo::NOTICIAS, fn () => NoticiaResource::collection(
            Noticia::publicadas()
                ->with('entidad')
                ->orderByDesc('fecha_publicacion')
                ->orderByDesc('fecha_creacion')
                ->get()
        )->resolve()));
    }
}
