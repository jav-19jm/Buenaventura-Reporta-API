<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuariosAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->administrador()->create();
    }

    public function test_lista_todos_los_usuarios_con_su_correo(): void
    {
        $ciudadano = User::factory()->create();

        $this->actingAs($this->admin, 'api')->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['email' => $ciudadano->email]);
    }

    public function test_suspender_guarda_el_motivo_y_reactivar_lo_limpia(): void
    {
        $ciudadano = User::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$ciudadano->id}/status", ['estado' => 'suspendido', 'motivo_bloqueo' => 'Spam'])
            ->assertOk()
            ->assertJsonPath('estado', 'suspendido')
            ->assertJsonPath('motivo_bloqueo', 'Spam');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$ciudadano->id}/status", ['estado' => 'activo'])
            ->assertOk()
            ->assertJsonPath('motivo_bloqueo', null);
    }

    public function test_el_admin_no_puede_bloquearse_ni_cambiar_su_propio_rol(): void
    {
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$this->admin->id}/status", ['estado' => 'suspendido'])
            ->assertUnprocessable();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$this->admin->id}/role", ['rol' => 'ciudadano'])
            ->assertUnprocessable();
    }

    public function test_promover_a_administrador_y_no_asignar_rol_entidad(): void
    {
        $ciudadano = User::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$ciudadano->id}/role", ['rol' => 'administrador'])
            ->assertOk()
            ->assertJsonPath('rol', 'administrador');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$ciudadano->id}/role", ['rol' => 'entidad'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rol');
    }
}
