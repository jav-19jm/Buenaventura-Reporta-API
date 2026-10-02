<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Se puede ejecutar varias veces sin duplicar datos.
     */
    public function run(): void
    {
        $this->call(CatalogoSeeder::class);

        // Administrador inicial (credenciales desde el .env)
        $adminEmail = env('ADMIN_EMAIL', 'admin@buenaventura.local');
        if (! User::where('email', $adminEmail)->exists()) {
            User::factory()->administrador()->create([
                'nombre_completo' => 'Administrador',
                'email' => $adminEmail,
                'password' => env('ADMIN_PASSWORD', 'password'),
                'telefono' => null,
            ]);
        }

        // Ciudadano y datos de prueba solo en entornos locales
        if (app()->environment('local')) {
            if (! User::where('email', 'ciudadano@buenaventura.local')->exists()) {
                User::factory()->create([
                    'nombre_completo' => 'Ciudadano de Prueba',
                    'email' => 'ciudadano@buenaventura.local',
                ]);
            }

            $this->call(DatosDemoSeeder::class);
        }
    }
}
