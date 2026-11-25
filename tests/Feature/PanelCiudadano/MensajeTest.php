<?php

namespace Tests\Feature\PanelCiudadano;

use App\Models\Entidad;
use App\Models\Mensaje;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MensajeTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_autor_envia_mensaje_y_se_notifica_a_administradores_y_entidad(): void
    {
        $entidad = Entidad::factory()->create();
        $reporte = Reporte::factory()->create(['id_entidad' => $entidad->id]);
        $admin = User::factory()->administrador()->create();
        $cuentaEntidad = User::factory()->entidad()->create();
        $cuentaEntidad->forceFill(['id_entidad' => $entidad->id])->save();

        $this->actingAs($reporte->usuario, 'api')
            ->postJson("/api/reports/{$reporte->id}/messages", ['mensaje' => '¿Hay novedades?'])
            ->assertCreated()
            ->assertJsonPath('tipo_remitente', 'usuario')
            ->assertJsonPath('perfiles.nombre_completo', $reporte->usuario->nombre_completo);

        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $admin->id, 'tipo' => 'nuevo_mensaje']);
        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $cuentaEntidad->id, 'tipo' => 'nuevo_mensaje']);
        $this->assertDatabaseMissing('notificaciones', ['id_usuario' => $reporte->id_usuario]);
    }

    public function test_la_entidad_asignada_puede_responder_y_se_notifica_al_ciudadano(): void
    {
        $entidad = Entidad::factory()->create();
        $reporte = Reporte::factory()->create(['id_entidad' => $entidad->id]);
        $cuentaEntidad = User::factory()->entidad()->create();
        $cuentaEntidad->forceFill(['id_entidad' => $entidad->id])->save();

        $this->actingAs($cuentaEntidad, 'api')
            ->postJson("/api/reports/{$reporte->id}/messages", ['mensaje' => 'Ya enviamos una cuadrilla.'])
            ->assertCreated()
            ->assertJsonPath('tipo_remitente', 'entidad');

        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $reporte->id_usuario, 'titulo' => 'Nueva respuesta institucional']);
    }

    public function test_un_usuario_ajeno_no_puede_leer_ni_escribir_en_el_chat(): void
    {
        $reporte = Reporte::factory()->create();
        $ajeno = User::factory()->create();

        $this->actingAs($ajeno, 'api')->getJson("/api/reports/{$reporte->id}/messages")->assertForbidden();
        $this->actingAs($ajeno, 'api')->postJson("/api/reports/{$reporte->id}/messages", ['mensaje' => 'Hola'])->assertForbidden();
    }

    public function test_el_autor_edita_su_mensaje_dentro_del_limite_de_tiempo(): void
    {
        $reporte = Reporte::factory()->create();
        $mensaje = $reporte->mensajes()->create(['id_remitente' => $reporte->id_usuario, 'tipo_remitente' => 'usuario', 'mensaje' => 'Original']);

        $this->actingAs($reporte->usuario, 'api')
            ->patchJson("/api/messages/{$mensaje->id}", ['mensaje' => 'Editado'])
            ->assertOk()
            ->assertJsonPath('mensaje', 'Editado');
    }

    public function test_no_se_puede_editar_ni_eliminar_despues_de_5_minutos(): void
    {
        $reporte = Reporte::factory()->create();
        $mensaje = $reporte->mensajes()->create(['id_remitente' => $reporte->id_usuario, 'tipo_remitente' => 'usuario', 'mensaje' => 'Original']);

        $this->travel(6)->minutes();

        $this->actingAs($reporte->usuario, 'api')
            ->patchJson("/api/messages/{$mensaje->id}", ['mensaje' => 'Tarde'])
            ->assertForbidden();

        $this->actingAs($reporte->usuario, 'api')
            ->deleteJson("/api/messages/{$mensaje->id}")
            ->assertForbidden();

        $this->assertSame('Original', Mensaje::find($mensaje->id)->mensaje);
    }
}
