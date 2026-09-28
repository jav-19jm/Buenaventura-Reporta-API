<?php

namespace App\Http\Controllers;

use App\Http\Resources\NoticiaResource;
use App\Models\CategoriaReporte;
use App\Models\Entidad;
use App\Models\Noticia;
use App\Models\Servicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catálogos de lectura pública que alimentan el mapa y los formularios.
 */
class CatalogoController extends Controller
{
    /** Categorías de incidencia disponibles para reportar */
    public function categorias(): JsonResponse
    {
        return response()->json(CategoriaReporte::activas()->orderBy('nombre')->get());
    }

    /** Entidades activas (selector de entidad responsable) */
    public function entidades(): JsonResponse
    {
        return response()->json(Entidad::activas()->orderBy('nombre')->get());
    }

    /** Servicios de la ciudad que se muestran en el mapa */
    public function servicios(): JsonResponse
    {
        return response()->json(Servicio::activos()->orderBy('nombre')->get());
    }

    /** Noticias publicadas, las más recientes primero */
    public function noticias(): AnonymousResourceCollection
    {
        $noticias = Noticia::publicadas()
            ->with('entidad')
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('fecha_creacion')
            ->get();

        return NoticiaResource::collection($noticias);
    }
}
