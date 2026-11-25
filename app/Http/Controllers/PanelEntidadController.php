<?php

namespace App\Http\Controllers;

use App\Enums\EstadoReporte;
use App\Http\Resources\ReporteResource;
use App\Models\ActividadEntidad;
use App\Models\Entidad;
use App\Support\ArchivosPublicos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Panel institucional. Todas las acciones se limitan a la entidad vinculada
 * a la cuenta autenticada (middleware "con_entidad").
 */
class PanelEntidadController extends Controller
{
    /** Datos de la entidad de la cuenta autenticada */
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->entidad($request));
    }

    /**
     * La entidad edita sus datos de contacto y presentación. Nombre, slug,
     * tipo y correo de acceso solo los cambia la administración.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:30'],
            'sitio_web' => ['sometimes', 'nullable', 'url', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ], [
            'sitio_web.url' => 'El sitio web debe ser una URL válida (incluye https://).',
        ]);

        $entidad = $this->entidad($request);
        $entidad->update($validated);

        ActividadEntidad::registrar(
            $entidad->id,
            ActividadEntidad::ACTUALIZACION,
            'Perfil institucional actualizado',
            'Campos modificados: '.implode(', ', array_keys($validated)).'.'
        );

        return response()->json($entidad);
    }

    public function subirLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $entidad = $this->entidad($request);
        $url = ArchivosPublicos::guardar($request->file('logo'), 'logos', $entidad->logo_url);
        $entidad->update(['logo_url' => $url]);

        ActividadEntidad::registrar($entidad->id, ActividadEntidad::ACTUALIZACION, 'Logo actualizado', 'Se cambió el logo institucional.');

        return response()->json(['url' => $url]);
    }

    /** Reportes asignados a la entidad, los más recientes primero */
    public function reportes(Request $request): AnonymousResourceCollection
    {
        $reportes = $this->entidad($request)->reportes()
            ->visibles()
            ->with(['usuario', 'entidad'])
            ->latest('fecha_creacion')
            ->get();

        return ReporteResource::collection($reportes);
    }

    /** Conteo de los reportes asignados por estado */
    public function estadisticas(Request $request): JsonResponse
    {
        $porEstado = $this->entidad($request)->reportes()
            ->visibles()
            ->toBase()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $stats = ['total' => (int) $porEstado->sum()];
        foreach (EstadoReporte::values() as $estado) {
            $stats[$estado] = (int) ($porEstado[$estado] ?? 0);
        }

        return response()->json($stats);
    }

    /** Últimas 50 acciones de la auditoría */
    public function actividad(Request $request): JsonResponse
    {
        $actividad = ActividadEntidad::where('id_entidad', $this->entidad($request)->id)
            ->latest('fecha_creacion')
            ->limit(50)
            ->get();

        return response()->json($actividad);
    }

    private function entidad(Request $request): Entidad
    {
        return $request->user()->entidad;
    }
}
