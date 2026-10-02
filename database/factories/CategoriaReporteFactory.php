<?php

namespace Database\Factories;

use App\Models\CategoriaReporte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaReporte>
 */
class CategoriaReporteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'icono' => 'alert-triangle',
            'color' => 'text-blue-600',
            'esta_activa' => true,
        ];
    }
}
