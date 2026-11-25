<?php

namespace App\Http\Controllers;

use App\Enums\EstadoReporte;
use App\Enums\RolUsuario;
use App\Http\Requests\StoreReporteRequest;
use App\Http\Resources\ReporteResource;
use App\Models\CategoriaReporte;
use App\Models\Reporte;
use App\Services\GestionReportesService;
use App\Services\InsigniaService;
use App\Support\ArchivosPublicos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReporteController extends Controller
{
    /** Reportes visibles para el mapa público y el panel ciudadano */
    public function index(): AnonymousResourceCollection
    {
        $reportes = Reporte::visibles()
            ->with(['usuario', 'entidad'])
            ->latest('fecha_creacion')
            ->get();

        return ReporteResource::collection($reportes);
    }

    /** Detalle de un reporte (los eliminados solo los ve su autor o la administración) */
    public function show(Request $request, Reporte $reporte): ReporteResource
    {
        if (! $reporte->visible) {
            $user = auth('api')->user();
            abort_unless(
                $user && ($user->id === $reporte->id_usuario || $user->tieneRol(RolUsuario::Administrador, RolUsuario::Moderador)),
                404,
                'El reporte no existe.'
            );
        }

        return new ReporteResource($reporte->load(['usuario', 'entidad']));
    }

    /** Reportes del ciudadano autenticado */
    public function misReportes(Request $request): AnonymousResourceCollection
    {
        $reportes = $request->user()->reportes()
            ->visibles()
            ->with('entidad')
            ->latest('fecha_creacion')
            ->get();

        return ReporteResource::collection($reportes);
    }

    /**
     * Crea un reporte. Si no se elige entidad, se asigna la responsable por
     * defecto de la categoría.
     */
    public function store(StoreReporteRequest $request, InsigniaService $insignias): JsonResponse
    {
        $user = $request->user();
        $datos = $request->validated();

        $datos['id_entidad'] ??= CategoriaReporte::where('nombre', $datos['categoria'])->value('id_entidad');

        $reporte = DB::transaction(function () use ($user, $datos) {
            $reporte = new Reporte($datos);
            $reporte->id_usuario = $user->id;
            $reporte->save();

            $user->increment('reportes_creados');
            $reporte->registrarHistorial('creado', $user, null, $reporte->estado->value);

            return $reporte;
        });

        $insignias->evaluar($user->fresh());

        return (new ReporteResource($reporte->fresh()->load(['usuario', 'entidad'])))
            ->response()
            ->setStatusCode(201);
    }

    /** El autor oculta su reporte (borrado lógico) */
    public function destroy(Request $request, Reporte $reporte): JsonResponse
    {
        Gate::authorize('gestionar', $reporte);

        $reporte->forceFill(['visible' => false])->save();
        $reporte->registrarHistorial('eliminado', $request->user());

        return response()->json(['message' => 'Reporte eliminado.']);
    }

    /** Cambio de estado por la administración o la entidad asignada */
    public function cambiarEstado(Request $request, Reporte $reporte, GestionReportesService $gestion): ReporteResource
    {
        Gate::authorize('cambiarEstado', $reporte);

        $validated = $request->validate([
            'estado' => ['required', Rule::enum(EstadoReporte::class)],
        ]);

        $reporte = $gestion->cambiarEstado($reporte, EstadoReporte::from($validated['estado']), $request->user());

        return new ReporteResource($reporte->load(['usuario', 'entidad']));
    }

    /** Sube o reemplaza la imagen del reporte */
    public function subirImagen(Request $request, Reporte $reporte): JsonResponse
    {
        Gate::authorize('gestionar', $reporte);

        $request->validate([
            'imagen' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $url = ArchivosPublicos::guardar($request->file('imagen'), 'reportes', $reporte->url_imagen);
        $reporte->forceFill(['url_imagen' => $url])->save();

        return response()->json(['url' => $url]);
    }
}
