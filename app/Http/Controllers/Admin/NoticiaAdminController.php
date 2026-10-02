<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoNotificacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NoticiaRequest;
use App\Http\Resources\NoticiaResource;
use App\Models\Noticia;
use App\Services\Notificador;
use App\Support\ArchivosPublicos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoticiaAdminController extends Controller
{
    public function __construct(private Notificador $notificador) {}

    /** Todas las noticias (publicadas y borradores) */
    public function index(): AnonymousResourceCollection
    {
        return NoticiaResource::collection(Noticia::with('entidad')->latest('fecha_creacion')->get());
    }

    public function store(NoticiaRequest $request): JsonResponse
    {
        $noticia = new Noticia($request->validated());
        $publicar = $noticia->esta_publicada;
        $noticia->esta_publicada = false;
        $noticia->save();

        if ($publicar) {
            $this->publicar($noticia);
        }

        return (new NoticiaResource($noticia->load('entidad')))->response()->setStatusCode(201);
    }

    public function update(NoticiaRequest $request, Noticia $noticia): NoticiaResource
    {
        $datos = $request->validated();
        $quierePublicada = $datos['esta_publicada'] ?? $noticia->esta_publicada;
        unset($datos['esta_publicada']);

        $noticia->update($datos);
        $this->aplicarPublicacion($noticia, $quierePublicada);

        return new NoticiaResource($noticia->load('entidad'));
    }

    /** Publica o retira una noticia */
    public function cambiarPublicacion(Request $request, Noticia $noticia): NoticiaResource
    {
        $validated = $request->validate(['esta_publicada' => ['required', 'boolean']]);

        $this->aplicarPublicacion($noticia, (bool) $validated['esta_publicada']);

        return new NoticiaResource($noticia->load('entidad'));
    }

    public function subirImagen(Request $request, Noticia $noticia): JsonResponse
    {
        $request->validate([
            'imagen' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $url = ArchivosPublicos::guardar($request->file('imagen'), 'noticias', $noticia->url_imagen);
        $noticia->update(['url_imagen' => $url]);

        return response()->json(['url' => $url]);
    }

    public function destroy(Noticia $noticia): JsonResponse
    {
        if ($noticia->url_imagen) {
            ArchivosPublicos::eliminar($noticia->url_imagen);
        }

        $noticia->delete();

        return response()->json(['message' => 'Noticia eliminada.']);
    }

    private function aplicarPublicacion(Noticia $noticia, bool $publicada): void
    {
        if ($publicada && ! $noticia->esta_publicada) {
            $this->publicar($noticia);
        } elseif (! $publicada && $noticia->esta_publicada) {
            $noticia->update(['esta_publicada' => false]);
        }
    }

    /**
     * Marca la noticia como publicada. Solo se avisa a los ciudadanos la
     * primera vez que se publica (fecha_publicacion vacía).
     */
    private function publicar(Noticia $noticia): void
    {
        $primeraVez = $noticia->fecha_publicacion === null;

        $noticia->update([
            'esta_publicada' => true,
            'fecha_publicacion' => $noticia->fecha_publicacion ?? now(),
        ]);

        if ($primeraVez) {
            $this->notificador->notificar(
                $this->notificador->idsCiudadanos(),
                TipoNotificacion::AlertaSistema,
                'Nueva noticia publicada',
                "Se ha publicado: {$noticia->titulo}. ¡Entérate de las novedades!",
            );
        }
    }
}
