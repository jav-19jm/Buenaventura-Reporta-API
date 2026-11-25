<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReporteResource;
use App\Models\Entidad;
use App\Models\Reporte;
use App\Services\GestionReportesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ReporteAdminController extends Controller
{
    /** Todos los reportes, incluidos los ocultos */
    public function index(): AnonymousResourceCollection
    {
        $reportes = Reporte::with(['usuario', 'entidad'])->latest('fecha_creacion')->get();

        return ReporteResource::collection($reportes);
    }

    public function asignarEntidad(Request $request, Reporte $reporte, GestionReportesService $gestion): ReporteResource
    {
        $validated = $request->validate([
            'id_entidad' => ['required', 'uuid', Rule::exists('entidades', 'id')],
        ]);

        $reporte = $gestion->asignarEntidad($reporte, Entidad::findOrFail($validated['id_entidad']), $request->user());

        return new ReporteResource($reporte->load(['usuario', 'entidad']));
    }

    /** Oculta el reporte (borrado lógico) */
    public function destroy(Request $request, Reporte $reporte): JsonResponse
    {
        $reporte->forceFill(['visible' => false])->save();
        $reporte->registrarHistorial('eliminado_por_administracion', $request->user());

        return response()->json(['message' => 'Reporte eliminado.']);
    }
}
