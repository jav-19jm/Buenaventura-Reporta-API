<?php

namespace Database\Seeders;

use App\Models\CategoriaReporte;
use App\Models\Entidad;
use App\Models\Insignia;
use Illuminate\Database\Seeder;

/**
 * Datos base que la plataforma necesita para funcionar: entidades,
 * categorías de incidencia e insignias. Es idempotente (updateOrCreate).
 */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $entidades = collect([
            ['slug' => 'aseo', 'nombre' => 'Empresa de Aseo Municipal', 'tipo' => 'ambiente', 'color' => '#16a34a', 'descripcion' => 'Recolección de residuos y limpieza de espacios públicos.'],
            ['slug' => 'movilidad', 'nombre' => 'Secretaría de Movilidad', 'tipo' => 'infraestructura', 'color' => '#2563eb', 'descripcion' => 'Semaforización, señalización y tránsito.'],
            ['slug' => 'acueducto', 'nombre' => 'Acueducto Municipal', 'tipo' => 'servicios-publicos', 'color' => '#0891b2', 'descripcion' => 'Suministro de agua potable y alcantarillado.'],
            ['slug' => 'obras', 'nombre' => 'Secretaría de Obras Públicas', 'tipo' => 'infraestructura', 'color' => '#ea580c', 'descripcion' => 'Vías, alumbrado público e infraestructura urbana.'],
            ['slug' => 'policia', 'nombre' => 'Policía Nacional', 'tipo' => 'seguridad', 'color' => '#4f46e5', 'descripcion' => 'Seguridad ciudadana y convivencia.'],
            ['slug' => 'bomberos', 'nombre' => 'Cuerpo de Bomberos', 'tipo' => 'seguridad', 'color' => '#dc2626', 'descripcion' => 'Atención de incendios y emergencias.'],
        ])->mapWithKeys(fn (array $datos) => [
            $datos['slug'] => Entidad::updateOrCreate(['slug' => $datos['slug']], $datos),
        ]);

        $categorias = [
            ['nombre' => 'Luminaria dañada', 'icono' => 'lightbulb', 'color' => 'text-yellow-600', 'entidad' => 'obras'],
            ['nombre' => 'Basura en vía pública', 'icono' => 'trash', 'color' => 'text-green-600', 'entidad' => 'aseo'],
            ['nombre' => 'Semáforo dañado', 'icono' => 'traffic-cone', 'color' => 'text-orange-600', 'entidad' => 'movilidad'],
            ['nombre' => 'Fuga de agua', 'icono' => 'droplet', 'color' => 'text-blue-600', 'entidad' => 'acueducto'],
            ['nombre' => 'Incendio', 'icono' => 'flame', 'color' => 'text-red-600', 'entidad' => 'bomberos'],
            ['nombre' => 'Alteración del orden público', 'icono' => 'alert-triangle', 'color' => 'text-purple-600', 'entidad' => 'policia'],
            ['nombre' => 'Hueco en la vía', 'icono' => 'hard-hat', 'color' => 'text-orange-600', 'entidad' => 'obras'],
        ];

        foreach ($categorias as $categoria) {
            CategoriaReporte::updateOrCreate(['nombre' => $categoria['nombre']], [
                'icono' => $categoria['icono'],
                'color' => $categoria['color'],
                'id_entidad' => $entidades[$categoria['entidad']]->id,
            ]);
        }

        // Los nombres deben coincidir con las reglas de App\Services\InsigniaService
        $insignias = [
            ['nombre' => 'Primer Reporte', 'icono' => '🎯', 'descripcion' => 'Creaste tu primer reporte.', 'requisito_texto' => 'Crear 1 reporte'],
            ['nombre' => '10 Reportes', 'icono' => '📣', 'descripcion' => 'Ciudadano comprometido.', 'requisito_texto' => 'Crear 10 reportes'],
            ['nombre' => '50 Reportes', 'icono' => '🏆', 'descripcion' => 'Guardián de la ciudad.', 'requisito_texto' => 'Crear 50 reportes'],
            ['nombre' => 'Solucionador', 'icono' => '✅', 'descripcion' => 'Uno de tus reportes fue resuelto.', 'requisito_texto' => 'Tener 1 reporte resuelto'],
            ['nombre' => 'Embajador', 'icono' => '⭐', 'descripcion' => 'La comunidad valora tus aportes.', 'requisito_texto' => 'Alcanzar 100 puntos de reputación'],
        ];

        foreach ($insignias as $insignia) {
            Insignia::updateOrCreate(['nombre' => $insignia['nombre']], $insignia);
        }
    }
}
