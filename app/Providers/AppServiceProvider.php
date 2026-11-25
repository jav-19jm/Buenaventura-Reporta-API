<?php

namespace App\Providers;

use App\Models\CategoriaReporte;
use App\Models\Entidad;
use App\Models\Noticia;
use App\Models\Servicio;
use App\Support\CacheCatalogo;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Los recursos se devuelven sin el envoltorio {"data": ...}: el frontend
        // consume directamente el objeto o el arreglo.
        JsonResource::withoutWrapping();

        // Invalidación de la caché de catálogos públicos
        CacheCatalogo::invalidarAlCambiar(CategoriaReporte::class, CacheCatalogo::CATEGORIAS);
        CacheCatalogo::invalidarAlCambiar(Servicio::class, CacheCatalogo::SERVICIOS);
        CacheCatalogo::invalidarAlCambiar(Noticia::class, CacheCatalogo::NOTICIAS);
        // Las noticias incluyen el nombre de su entidad
        CacheCatalogo::invalidarAlCambiar(Entidad::class, CacheCatalogo::ENTIDADES, CacheCatalogo::NOTICIAS);
    }
}
