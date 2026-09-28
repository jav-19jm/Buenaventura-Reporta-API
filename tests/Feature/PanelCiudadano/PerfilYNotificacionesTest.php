<?php

namespace Tests\Feature\PanelCiudadano;

use App\Models\Insignia;
use App\Models\Notificacion;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ImagenDePrueba;
use Tests\TestCase;

class PerfilYNotificacionesTest extends TestCase
{
    use ImagenDePrueba, RefreshDatabase;

    public function test_perfil_publico_no_expone_datos_de_contacto(): void
    {
        $user = User::factory()->create(['telefono' => '3001234567']);

        $this->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('nombre_completo', $user->nombre_completo)
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('telefono');
    }

    public function test_el_propio_usuario_recibe_su_perfil_completo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_insignias_del_usuario(): void
    {
        $user = User::factory()->create();
        $insignia = Insignia::create(['nombre' => 'Primer Reporte', 'icono' => '🎯', 'requisito_texto' => 'Crear 1 reporte']);
        $user->insignias()->attach($insignia->id, ['fecha_obtencion' => now()]);

        $this->getJson("/api/users/{$user->id}/badges")
            ->assertOk()
            ->assertJsonPath('0.nombre', 'Primer Reporte')
            ->assertJsonPath('0.icono', '🎯');
    }

    public function test_subir_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->post('/api/users/me/avatar', ['avatar' => $this->imagenPng('yo.png')], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('user.url_avatar', $response->json('url'));
    }

    public function test_notificaciones_propias_se_listan_marcan_y_eliminan(): void
    {
        $user = User::factory()->create();
        $propia = Notificacion::create(['id_usuario' => $user->id, 'tipo' => 'mencion', 'titulo' => 'Hola', 'mensaje' => 'Mensaje']);
        $ajena = Notificacion::create(['id_usuario' => User::factory()->create()->id, 'tipo' => 'mencion', 'titulo' => 'Otra', 'mensaje' => 'Mensaje']);

        $this->actingAs($user, 'api')->getJson('/api/users/me/notifications')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $propia->id);

        $this->actingAs($user, 'api')->patchJson("/api/notifications/{$propia->id}/read")->assertOk()->assertJsonPath('esta_leida', true);
        $this->actingAs($user, 'api')->patchJson("/api/notifications/{$ajena->id}/read")->assertNotFound();
        $this->actingAs($user, 'api')->deleteJson("/api/notifications/{$propia->id}")->assertOk();

        $this->assertDatabaseMissing('notificaciones', ['id' => $propia->id]);
    }

    public function test_denunciar_usuario_notifica_a_los_administradores(): void
    {
        $admin = User::factory()->administrador()->create();
        $reporte = Reporte::factory()->create();

        $this->actingAs(User::factory()->create(), 'api')
            ->postJson("/api/users/{$reporte->id_usuario}/report-abuse", ['motivo' => 'Reporte falso', 'id_reporte' => $reporte->id])
            ->assertOk();

        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $admin->id, 'tipo' => 'alerta_sistema', 'id_reporte' => $reporte->id]);
    }
}
