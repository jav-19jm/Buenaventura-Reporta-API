<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entidad;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Indicadores del tablero de administración (calculados en la base de datos).
 */
class EstadisticasController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $reportes = Reporte::visibles();

        return response()->json([
            'reports' => [
                'total' => (clone $reportes)->count(),
                'byStatus' => $this->contarPor(clone $reportes, 'estado'),
                'byCategory' => $this->contarPor(clone $reportes, 'categoria'),
            ],
            'users' => [
                'total' => User::count(),
                'byStatus' => $this->contarPor(User::query(), 'estado'),
            ],
            'entities' => Entidad::count(),
        ]);
    }

    /** @return array<string, int> */
    private function contarPor($consulta, string $columna): array
    {
        return $consulta->toBase()
            ->selectRaw("{$columna} as clave, count(*) as total")
            ->groupBy($columna)
            ->pluck('total', 'clave')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
