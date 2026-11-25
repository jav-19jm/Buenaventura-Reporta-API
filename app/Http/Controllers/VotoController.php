<?php

namespace App\Http\Controllers;

use App\Enums\TipoVoto;
use App\Http\Resources\ReporteResource;
use App\Models\Reporte;
use App\Services\VotacionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VotoController extends Controller
{
    /** Registra o cambia el voto del usuario sobre un reporte */
    public function store(Request $request, Reporte $reporte, VotacionService $votacion): ReporteResource
    {
        abort_unless($reporte->visible, 404, 'El reporte no existe.');

        $validated = $request->validate([
            'tipo_voto' => ['required', Rule::enum(TipoVoto::class)],
        ]);

        $reporte = $votacion->votar($reporte, $request->user(), TipoVoto::from($validated['tipo_voto']));

        return new ReporteResource($reporte);
    }
}
