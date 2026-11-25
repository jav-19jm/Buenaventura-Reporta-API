<?php

namespace Tests\Feature\Admin;

use App\Models\Entidad;
use App\Models\Insignia;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->administrador()->create();
    }

    public function test_listado_admin_incluye_reportes_ocultos(): void
    {
        Reporte::factory()->create();
        Reporte::factory()->oculto()->create();

        $this->actingAs($this->admin, 'api')->getJson('/api/admin/reports')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_resolver_reporte_notifica_suma_resueltos_y_otorga_insignia(): void
    {
        Insignia::create(['nombre' => 'Solucionador', 'icono' => '✅']);
        $reporte = Reporte::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/reports/{$reporte->id}/status", ['estado' => 'resuelto'])
            ->assertOk()
            ->assertJsonPath('estado', 'resuelto')
            ->assertJsonPath('visible', true);

        $autor = $reporte->usuario->fresh();
        $this->assertSame(1, $autor->reportes_resueltos);
        $this->assertTrue($autor->insignias()->where('nombre', 'Solucionador')->exists());
        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $autor->id, 'tipo' => 'reporte_resuelto']);
        $this->assertDatabaseHas('historial_reportes', ['id_reporte' => $reporte->id, 'accion' => 'cambio_estado', 'valor_nuevo' => 'resuelto']);
    }

    public function test_reabrir_un_reporte_resuelto_descuenta_el_contador(): void
    {
        $reporte = Reporte::factory()->create();

        $this->actingAs($this->admin, 'api')->patchJson("/api/reports/{$reporte->id}/status", ['estado' => 'resuelto']);
        $this->actingAs($this->admin, 'api')->patchJson("/api/reports/{$reporte->id}/status", ['estado' => 'en_proceso'])->assertOk();

        $this->assertSame(0, $reporte->usuario->fresh()->reportes_resueltos);
    }

    public function test_solo_administracion_o_entidad_asignada_cambian_el_estado(): void
    {
        $entidad = Entidad::factory()->create();
        $reporte = Reporte::factory()->create(['id_entidad' => $entidad->id]);
        $cuentaEntidad = User::factory()->entidad()->create();
        $cuentaEntidad->forceFill(['id_entidad' => $entidad->id])->save();

        $this->actingAs($reporte->usuario, 'api')
            ->patchJson("/api/reports/{$reporte->id}/status", ['estado' => 'resuelto'])
            ->assertForbidden();

        $this->actingAs($cuentaEntidad, 'api')
            ->patchJson("/api/reports/{$reporte->id}/status", ['estado' => 'en_proceso'])
            ->assertOk();
    }

    public function test_asignar_entidad_notifica_al_autor_y_a_la_entidad(): void
    {
        $entidad = Entidad::factory()->create();
        $cuentaEntidad = User::factory()->entidad()->create();
        $cuentaEntidad->forceFill(['id_entidad' => $entidad->id])->save();
        $reporte = Reporte::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/reports/{$reporte->id}/entity", ['id_entidad' => $entidad->id])
            ->assertOk()
            ->assertJsonPath('entidades.id', $entidad->id);

        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $reporte->id_usuario, 'titulo' => 'Reporte asignado']);
        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $cuentaEntidad->id, 'titulo' => 'Nuevo reporte asignado']);
    }

    public function test_eliminar_reporte_lo_oculta(): void
    {
        $reporte = Reporte::factory()->create();

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/reports/{$reporte->id}")->assertOk();

        $this->assertFalse($reporte->fresh()->visible);
    }

    public function test_estadisticas_por_estado_categoria_y_usuarios(): void
    {
        Reporte::factory()->count(2)->create(['categoria' => 'Incendio', 'estado' => 'pendiente']);
        Reporte::factory()->create(['categoria' => 'Fuga de agua', 'estado' => 'resuelto']);
        Reporte::factory()->oculto()->create();

        $this->actingAs($this->admin, 'api')->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('reports.total', 3)
            ->assertJsonPath('reports.byStatus.pendiente', 2)
            ->assertJsonPath('reports.byCategory.Incendio', 2)
            ->assertJsonPath('users.total', User::count());
    }
}
