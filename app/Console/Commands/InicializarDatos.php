<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

/**
 * Carga el catálogo inicial y el administrador solo si la base no tiene
 * usuarios. Lo ejecuta el contenedor al arrancar; en una base con datos
 * (por ejemplo, la importada de Supabase) no hace nada.
 */
class InicializarDatos extends Command
{
    protected $signature = 'app:inicializar-datos';

    protected $description = 'Siembra el catálogo y el administrador inicial si la base está vacía';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->line('> La base ya tiene datos: no se siembra nada.');

            return self::SUCCESS;
        }

        $this->info('> Base vacía: sembrando catálogo y administrador inicial...');
        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
