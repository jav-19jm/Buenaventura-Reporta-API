<?php

namespace Database\Factories;

use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Servicio>
 */
class ServicioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'tipo' => 'salud',
            'latitud' => fake()->latitude(3.85, 3.90),
            'longitud' => fake()->longitude(-77.08, -77.00),
            'direccion' => fake()->streetAddress(),
            'esta_activo' => true,
        ];
    }
}
