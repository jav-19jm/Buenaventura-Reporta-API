<?php

namespace Tests\Feature\Admin;

use App\Models\Noticia;
use App\Models\Notificacion;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ImagenDePrueba;
use Tests\TestCase;

/**
 * Noticias y servicios del mapa.
 */
class ContenidosAdminTest extends TestCase
{
    use ImagenDePrueba, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->administrador()->create();
    }

    public function test_crear_noticia_publicada_notifica_a_los_ciudadanos_activos(): void
    {
        $ciudadano = User::factory()->create();
        $suspendido = User::factory()->suspendido()->create();

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/news', ['titulo' => 'Corte de agua', 'contenido' => 'Mañana', 'esta_publicada' => true, 'id_entidad' => '', 'url_imagen' => ''])
            ->assertCreated()
            ->assertJsonPath('esta_publicada', true);

        $this->assertNotNull(Noticia::first()->fecha_publicacion);
        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $ciudadano->id, 'titulo' => 'Nueva noticia publicada']);
        $this->assertDatabaseMissing('notificaciones', ['id_usuario' => $suspendido->id]);
        $this->assertDatabaseMissing('notificaciones', ['id_usuario' => $this->admin->id]);
    }

    public function test_borrador_no_notifica_y_publicarlo_solo_notifica_la_primera_vez(): void
    {
        User::factory()->create();

        $id = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/news', ['titulo' => 'Borrador', 'contenido' => 'Texto', 'esta_publicada' => false])
            ->assertCreated()
            ->json('id');
        $this->assertSame(0, Notificacion::count());

        $this->actingAs($this->admin, 'api')->patchJson("/api/admin/news/{$id}/publish", ['esta_publicada' => true])->assertOk();
        $this->actingAs($this->admin, 'api')->patchJson("/api/admin/news/{$id}/publish", ['esta_publicada' => false])->assertOk();
        $this->actingAs($this->admin, 'api')->patchJson("/api/admin/news/{$id}/publish", ['esta_publicada' => true])->assertOk();

        $this->assertSame(1, Notificacion::count());
    }

    public function test_listado_admin_incluye_borradores_y_se_puede_subir_imagen(): void
    {
        Storage::fake('public');
        Noticia::factory()->create();
        $borrador = Noticia::factory()->borrador()->create();

        $this->actingAs($this->admin, 'api')->getJson('/api/admin/news')->assertOk()->assertJsonCount(2);

        $response = $this->actingAs($this->admin, 'api')
            ->post("/api/admin/news/{$borrador->id}/image", ['imagen' => $this->imagenPng()], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame($response->json('url'), $borrador->fresh()->url_imagen);
    }

    public function test_crud_de_servicios(): void
    {
        $datos = ['nombre' => 'Hospital', 'tipo' => 'salud', 'latitud' => '3.88', 'longitud' => '-77.03', 'esta_activo' => true];

        $id = $this->actingAs($this->admin, 'api')->postJson('/api/admin/services', $datos)->assertCreated()->json('id');

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/services/{$id}", [...$datos, 'esta_activo' => false])
            ->assertOk()
            ->assertJsonPath('esta_activo', false);

        // Inactivo: sigue en la lista de administración, desaparece del mapa público
        $this->actingAs($this->admin, 'api')->getJson('/api/admin/services')->assertJsonCount(1);
        $this->getJson('/api/services')->assertJsonCount(0);

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/services/{$id}")->assertOk();
        $this->assertSame(0, Servicio::count());
    }

    public function test_servicio_requiere_coordenadas_validas(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/services', ['nombre' => 'X', 'tipo' => 'salud', 'latitud' => '200', 'longitud' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitud', 'longitud']);
    }
}
