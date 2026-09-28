<?php

namespace Database\Factories;

use App\Models\CategoriaReporte;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reporte>
 */
class ReporteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_usuario' => User::factory(),
            'titulo' => fake()->sentence(4),
            'descripcion' => fake()->paragraph(),
            'categoria' => fn () => CategoriaReporte::factory()->create()->nombre,
            'direccion_ubicacion' => fake()->streetAddress(),
            'latitud' => fake()->latitude(3.85, 3.90),
            'longitud' => fake()->longitude(-77.08, -77.00),
            'estado' => 'pendiente',
            'prioridad' => 'media',
            'visible' => true,
        ];
    }

    public function oculto(): static
    {
        return $this->state(fn (array $attributes) => ['visible' => false]);
    }
}
