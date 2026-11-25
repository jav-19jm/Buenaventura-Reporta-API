<?php

namespace Database\Factories;

use App\Models\Entidad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Entidad>
 */
class EntidadFactory extends Factory
{
    public function definition(): array
    {
        $nombre = fake()->unique()->company();

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'descripcion' => fake()->sentence(),
            'tipo' => 'servicios-publicos',
            'email' => fake()->unique()->companyEmail(),
            'telefono' => fake()->numerify('602#######'),
            'color' => fake()->hexColor(),
            'esta_activa' => true,
        ];
    }
}
