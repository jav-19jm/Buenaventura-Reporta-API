<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Administrador inicial (credenciales desde el .env)
        User::factory()->administrador()->create([
            'nombre_completo' => 'Administrador',
            'email' => env('ADMIN_EMAIL', 'admin@buenaventura.local'),
            'password' => env('ADMIN_PASSWORD', 'password'),
            'telefono' => null,
        ]);

        // Ciudadano de prueba solo en entornos locales
        if (app()->environment('local')) {
            User::factory()->create([
                'nombre_completo' => 'Ciudadano de Prueba',
                'email' => 'ciudadano@buenaventura.local',
            ]);
        }
    }
}
