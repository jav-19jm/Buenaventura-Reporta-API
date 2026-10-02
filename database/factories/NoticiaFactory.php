<?php

namespace Database\Factories;

use App\Models\Noticia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Noticia>
 */
class NoticiaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(),
            'contenido' => fake()->paragraphs(2, true),
            'categoria' => 'general',
            'esta_publicada' => true,
            'fecha_publicacion' => now(),
        ];
    }

    public function borrador(): static
    {
        return $this->state(fn (array $attributes) => ['esta_publicada' => false, 'fecha_publicacion' => null]);
    }
}
