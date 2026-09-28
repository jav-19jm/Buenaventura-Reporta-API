<?php

namespace Tests\Feature\PanelCiudadano;

use App\Models\CategoriaReporte;
use App\Models\Entidad;
use App\Models\Insignia;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ImagenDePrueba;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use ImagenDePrueba, RefreshDatabase;

    private function datosReporte(array $extra = []): array
    {
        $categoria = CategoriaReporte::factory()->create(['nombre' => 'Fuga de agua']);

        return [
            'titulo' => 'Tubo roto',
            'descripcion' => 'Sale agua de la acera.',
            'categoria' => $categoria->nombre,
            'direccion_ubicacion' => 'Calle 1 # 2-3',
            'latitud' => '3.8801',
            'longitud' => '-77.0311',
            ...$extra,
        ];
    }

    public function test_listado_publico_solo_incluye_reportes_visibles_con_autor_y_entidad(): void
    {
        $visible = Reporte::factory()->create(['id_entidad' => Entidad::factory()->create()->id]);
        Reporte::factory()->oculto()->create();

        $this->getJson('/api/reports')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $visible->id)
            ->assertJsonPath('0.perfiles.id', $visible->id_usuario)
            ->assertJsonPath('0.entidades.id', $visible->id_entidad)
            ->assertJsonMissingPath('0.perfiles.email');
    }

    public function test_crear_reporte_requiere_sesion(): void
    {
        $this->postJson('/api/reports', $this->datosReporte())->assertUnauthorized();
    }

    public function test_crear_reporte_asigna_autor_entidad_de_la_categoria_contador_e_insignia(): void
    {
        $entidad = Entidad::factory()->create();
        $user = User::factory()->create();
        Insignia::create(['nombre' => 'Primer Reporte', 'icono' => '🎯']);
        $datos = $this->datosReporte();
        CategoriaReporte::where('nombre', $datos['categoria'])->update(['id_entidad' => $entidad->id]);

        $response = $this->actingAs($user, 'api')->postJson('/api/reports', [...$datos, 'estado' => 'resuelto']);

        $response->assertCreated()
            ->assertJsonPath('id_usuario', $user->id)
            ->assertJsonPath('estado', 'pendiente')
            ->assertJsonPath('id_entidad', $entidad->id)
            ->assertJsonPath('entidades.nombre', $entidad->nombre);

        $this->assertSame(1, $user->fresh()->reportes_creados);
        $this->assertTrue($user->insignias()->where('nombre', 'Primer Reporte')->exists());
        $this->assertDatabaseHas('historial_reportes', ['id_reporte' => $response->json('id'), 'accion' => 'creado']);
    }

    public function test_crear_reporte_rechaza_categoria_inexistente(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/reports', $this->datosReporte(['categoria' => 'No existe']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categoria');
    }

    public function test_mis_reportes_devuelve_solo_los_propios_visibles(): void
    {
        $user = User::factory()->create();
        $propio = Reporte::factory()->create(['id_usuario' => $user->id]);
        Reporte::factory()->oculto()->create(['id_usuario' => $user->id]);
        Reporte::factory()->create();

        $this->actingAs($user, 'api')->getJson('/api/users/me/reports')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $propio->id);
    }

    public function test_solo_el_autor_puede_eliminar_su_reporte(): void
    {
        $reporte = Reporte::factory()->create();

        $this->actingAs(User::factory()->create(), 'api')
            ->deleteJson("/api/reports/{$reporte->id}")
            ->assertForbidden();

        $this->actingAs($reporte->usuario, 'api')
            ->deleteJson("/api/reports/{$reporte->id}")
            ->assertOk();

        $this->assertFalse($reporte->fresh()->visible);

        // El autor aún puede consultarlo; cualquier otra persona recibe 404
        $this->getJson("/api/reports/{$reporte->id}")->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/reports/{$reporte->id}")->assertNotFound();
    }

    public function test_el_autor_sube_la_imagen_del_reporte(): void
    {
        Storage::fake('public');
        $reporte = Reporte::factory()->create();

        $response = $this->actingAs($reporte->usuario, 'api')
            ->post("/api/reports/{$reporte->id}/image", ['imagen' => $this->imagenPng('foto.png')], ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertSame($response->json('url'), $reporte->fresh()->url_imagen);
        Storage::disk('public')->assertExists('reportes/'.basename($response->json('url')));
    }

    public function test_identificador_invalido_responde_404(): void
    {
        $this->getJson('/api/reports/no-es-un-uuid')->assertNotFound();
    }
}
