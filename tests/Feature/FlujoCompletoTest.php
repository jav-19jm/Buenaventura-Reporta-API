<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Recorre el ciclo de vida completo de un reporte a través de los tres
 * paneles, solo por HTTP y con tokens reales, como lo haría el frontend.
 */
class FlujoCompletoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simula una petición nueva: en la misma prueba Laravel y tymon/jwt-auth
     * conservan el usuario y el token ya resueltos en la petición anterior.
     */
    private function reiniciarSesion(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['tymon.jwt']->unsetToken();
        $this->app['tymon.jwt.auth']->unsetToken();
    }

    private function login(string $email, string $password): string
    {
        $this->reiniciarSesion();
        $this->defaultHeaders = [];

        return $this->postJson('/api/auth/login', ['email' => $email, 'password' => $password])
            ->assertOk()
            ->json('token');
    }

    private function como(string $token): static
    {
        $this->reiniciarSesion();

        return $this->withToken($token);
    }

    public function test_ciclo_de_vida_de_un_reporte_entre_ciudadano_administracion_y_entidad(): void
    {
        $this->seed(CatalogoSeeder::class);
        User::factory()->administrador()->create(['email' => 'admin@example.com']);
        $admin = $this->login('admin@example.com', 'password');

        // 1. La administración crea la entidad con su cuenta institucional
        $entidad = $this->como($admin)->postJson('/api/admin/entities', [
            'nombre' => 'Empresa de Energía', 'slug' => 'energia', 'tipo' => 'servicios-publicos',
            'email' => 'energia@example.com', 'password' => 'energia-123',
        ])->assertCreated()->json();

        // 2. Un ciudadano se registra, verifica su correo e inicia sesión
        $this->postJson('/api/auth/register', [
            'nombre_completo' => 'Ana Ciudadana', 'email' => 'ana@example.com', 'password' => 'ana-clave-1',
        ])->assertCreated();
        $ana = User::where('email', 'ana@example.com')->firstOrFail();
        $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $ana->id, 'hash' => sha1($ana->email)]))
            ->assertRedirect();
        $tokenAna = $this->login('ana@example.com', 'ana-clave-1');

        // 3. Crea un reporte; queda asignado a la entidad de la categoría y gana su primera insignia
        $reporte = $this->como($tokenAna)->postJson('/api/reports', [
            'titulo' => 'Poste caído', 'descripcion' => 'Peligro en la esquina', 'categoria' => 'Luminaria dañada',
            'direccion_ubicacion' => 'Centro', 'latitud' => '3.88', 'longitud' => '-77.03',
        ])->assertCreated()->assertJsonPath('entidades.slug', 'obras')->json();

        $this->getJson("/api/users/{$ana->id}/badges")->assertJsonPath('0.nombre', 'Primer Reporte');
        $this->getJson('/api/reports')->assertJsonFragment(['id' => $reporte['id']]);

        // 4. La administración reasigna el reporte a la nueva entidad y escribe en el chat
        $this->como($admin)->patchJson("/api/admin/reports/{$reporte['id']}/entity", ['id_entidad' => $entidad['id']])
            ->assertOk()->assertJsonPath('entidades.id', $entidad['id']);
        $this->como($admin)->postJson("/api/reports/{$reporte['id']}/messages", ['mensaje' => 'Lo remitimos a Energía.'])
            ->assertCreated()->assertJsonPath('tipo_remitente', 'moderador');

        // 5. La entidad ve el reporte en su panel, responde y lo resuelve
        $entidadToken = $this->login('energia@example.com', 'energia-123');
        $this->como($entidadToken)->getJson('/api/entity/reports')->assertJsonCount(1)->assertJsonPath('0.id', $reporte['id']);
        $this->como($entidadToken)->postJson("/api/reports/{$reporte['id']}/messages", ['mensaje' => 'Cuadrilla en camino.'])
            ->assertCreated()->assertJsonPath('tipo_remitente', 'entidad');
        $this->como($entidadToken)->patchJson("/api/reports/{$reporte['id']}/status", ['estado' => 'resuelto'])
            ->assertOk()->assertJsonPath('estado', 'resuelto');
        $this->como($entidadToken)->getJson('/api/entity/stats')->assertJsonPath('resuelto', 1);

        // 6. El ciudadano ve todo: chat de 2 mensajes, notificaciones y su contador de resueltos
        $this->como($tokenAna)->getJson("/api/reports/{$reporte['id']}/messages")->assertJsonCount(2);
        $titulos = collect($this->como($tokenAna)->getJson('/api/users/me/notifications')->json())->pluck('titulo');
        $this->assertTrue($titulos->contains('Reporte asignado'));
        $this->assertTrue($titulos->contains('Nueva respuesta institucional'));
        $this->assertTrue($titulos->contains('Estado de reporte actualizado'));
        $this->como($tokenAna)->getJson('/api/auth/me')->assertJsonPath('reportes_resueltos', 1);

        // 7. Un vecino vota y sube la reputación de Ana
        User::factory()->create(['email' => 'vecino@example.com']);
        $vecino = $this->login('vecino@example.com', 'password');
        $this->como($vecino)->postJson("/api/reports/{$reporte['id']}/votes", ['tipo_voto' => 'voto_positivo'])->assertOk();
        $this->getJson("/api/users/{$ana->id}")->assertJsonPath('puntuacion_reputacion', 1);

        // 8. La auditoría de la entidad registró login, asignación y cambio de estado
        $acciones = collect($this->como($entidadToken)->getJson('/api/entity/activity')->json())->pluck('tipo_accion');
        $this->assertEqualsCanonicalizing(['auth', 'reporte', 'reporte'], $acciones->all());

        // 9. La administración suspende a la cuenta de la entidad: su token deja de servir de inmediato
        $cuentaEntidad = User::where('email', 'energia@example.com')->firstOrFail();
        $this->como($admin)->patchJson("/api/admin/users/{$cuentaEntidad->id}/status", ['estado' => 'suspendido', 'motivo_bloqueo' => 'Auditoría'])
            ->assertOk();
        $this->como($entidadToken)->getJson('/api/entity')->assertForbidden()->assertJsonPath('codigo', 'cuenta_inactiva');
    }
}
