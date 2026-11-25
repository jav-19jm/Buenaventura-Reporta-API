<?php

namespace Tests\Feature\Entidad;

use App\Models\ActividadEntidad;
use App\Models\Entidad;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ImagenDePrueba;
use Tests\TestCase;

class PanelEntidadTest extends TestCase
{
    use ImagenDePrueba, RefreshDatabase;

    private Entidad $entidad;

    private User $cuenta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entidad = Entidad::factory()->create();
        $this->cuenta = User::factory()->entidad()->create(['email' => 'entidad@example.com']);
        $this->cuenta->forceFill(['id_entidad' => $this->entidad->id])->save();
    }

    public function test_solo_cuentas_de_entidad_vinculadas_acceden_al_panel(): void
    {
        $this->actingAs(User::factory()->create(), 'api')->getJson('/api/entity')->assertForbidden();

        $sinVincular = User::factory()->entidad()->create();
        $this->app['auth']->forgetGuards();
        $this->actingAs($sinVincular, 'api')->getJson('/api/entity')
            ->assertForbidden()
            ->assertJsonPath('codigo', 'sin_entidad');

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->cuenta, 'api')->getJson('/api/entity')
            ->assertOk()
            ->assertJsonPath('id', $this->entidad->id);
    }

    public function test_reportes_y_estadisticas_solo_de_la_propia_entidad(): void
    {
        Reporte::factory()->count(2)->create(['id_entidad' => $this->entidad->id, 'estado' => 'pendiente']);
        Reporte::factory()->create(['id_entidad' => $this->entidad->id, 'estado' => 'resuelto']);
        Reporte::factory()->oculto()->create(['id_entidad' => $this->entidad->id]);
        Reporte::factory()->create(['id_entidad' => Entidad::factory()->create()->id]);

        $this->actingAs($this->cuenta, 'api')->getJson('/api/entity/reports')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('0.entidades.id', $this->entidad->id);

        $this->actingAs($this->cuenta, 'api')->getJson('/api/entity/stats')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('pendiente', 2)
            ->assertJsonPath('resuelto', 1)
            ->assertJsonPath('en_proceso', 0);
    }

    public function test_la_entidad_edita_su_contacto_pero_no_su_nombre(): void
    {
        $nombre = $this->entidad->nombre;

        $this->actingAs($this->cuenta, 'api')
            ->putJson('/api/entity', ['sitio_web' => 'https://acueducto.gov.co', 'nombre' => 'Otro nombre'])
            ->assertOk()
            ->assertJsonPath('sitio_web', 'https://acueducto.gov.co')
            ->assertJsonPath('nombre', $nombre);

        $this->actingAs($this->cuenta, 'api')
            ->putJson('/api/entity', ['sitio_web' => 'no-es-url'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('actividad_entidades', ['id_entidad' => $this->entidad->id, 'tipo_accion' => 'update']);
    }

    public function test_subir_logo(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->cuenta, 'api')
            ->post('/api/entity/logo', ['logo' => $this->imagenPng('logo.png')], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame($response->json('url'), $this->entidad->fresh()->logo_url);
    }

    public function test_el_login_y_los_cambios_de_estado_quedan_en_la_auditoria(): void
    {
        $reporte = Reporte::factory()->create(['id_entidad' => $this->entidad->id]);

        $this->postJson('/api/auth/login', ['email' => 'entidad@example.com', 'password' => 'password'])->assertOk();

        $this->actingAs($this->cuenta, 'api')
            ->patchJson("/api/reports/{$reporte->id}/status", ['estado' => 'en_proceso'])
            ->assertOk();

        $this->actingAs($this->cuenta, 'api')->getJson('/api/entity/activity')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['tipo_accion' => 'auth'])
            ->assertJsonFragment(['tipo_accion' => 'reporte']);
    }

    public function test_la_entidad_no_cambia_el_estado_de_reportes_ajenos(): void
    {
        $ajeno = Reporte::factory()->create(['id_entidad' => Entidad::factory()->create()->id]);

        $this->actingAs($this->cuenta, 'api')
            ->patchJson("/api/reports/{$ajeno->id}/status", ['estado' => 'resuelto'])
            ->assertForbidden();

        $this->assertSame(0, ActividadEntidad::count());
    }
}
