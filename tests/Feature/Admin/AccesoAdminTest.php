<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesoAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function rutasAdmin(): array
    {
        return [
            'estadísticas' => ['get', '/api/admin/stats'],
            'usuarios' => ['get', '/api/admin/users'],
            'reportes' => ['get', '/api/admin/reports'],
            'entidades' => ['get', '/api/admin/entities'],
            'noticias' => ['get', '/api/admin/news'],
            'servicios' => ['get', '/api/admin/services'],
        ];
    }

    #[DataProvider('rutasAdmin')]
    public function test_rutas_admin_exigen_rol_administrador(string $metodo, string $ruta): void
    {
        $this->json($metodo, $ruta)->assertUnauthorized();

        $this->actingAs(User::factory()->create(), 'api')->json($metodo, $ruta)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->actingAs(User::factory()->administrador()->create(), 'api')->json($metodo, $ruta)->assertOk();
    }
}
