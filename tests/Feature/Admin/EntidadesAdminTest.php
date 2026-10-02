<?php

namespace Tests\Feature\Admin;

use App\Models\Entidad;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EntidadesAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->administrador()->create();
    }

    private function datosEntidad(array $extra = []): array
    {
        return [
            'nombre' => 'Acueducto Municipal',
            'slug' => 'acueducto',
            'descripcion' => '',
            'tipo' => 'servicios-publicos',
            'email' => 'acueducto@example.com',
            'telefono' => '6021234567',
            'color' => '#0891b2',
            'password' => 'clave-segura-1',
            ...$extra,
        ];
    }

    public function test_crear_entidad_crea_su_cuenta_institucional_verificada(): void
    {
        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/entities', $this->datosEntidad());

        $response->assertCreated()->assertJsonPath('slug', 'acueducto');

        $cuenta = User::where('email', 'acueducto@example.com')->firstOrFail();
        $this->assertSame('entidad', $cuenta->rol->value);
        $this->assertSame($response->json('id'), $cuenta->id_entidad);
        $this->assertTrue($cuenta->hasVerifiedEmail());

        // La cuenta puede iniciar sesión de inmediato
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/login', ['email' => 'acueducto@example.com', 'password' => 'clave-segura-1'])
            ->assertOk()
            ->assertJsonPath('user.rol', 'entidad');
    }

    public function test_crear_entidad_exige_contrasena_y_correo_libre(): void
    {
        User::factory()->create(['email' => 'ocupado@example.com']);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/entities', $this->datosEntidad(['email' => 'ocupado@example.com', 'password' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        $this->assertSame(0, Entidad::count());
    }

    public function test_editar_entidad_sincroniza_la_cuenta_y_cambia_la_contrasena(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/admin/entities', $this->datosEntidad());
        $entidad = Entidad::firstOrFail();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/entities/{$entidad->id}", $this->datosEntidad([
                'nombre' => 'Aguas de Buenaventura',
                'email' => 'aguas@example.com',
                'password' => 'nueva-clave-99',
            ]))
            ->assertOk()
            ->assertJsonPath('nombre', 'Aguas de Buenaventura');

        $cuenta = $entidad->usuarios()->firstOrFail();
        $this->assertSame('aguas@example.com', $cuenta->email);
        $this->assertSame('Aguas de Buenaventura', $cuenta->nombre_completo);
        $this->assertTrue(Hash::check('nueva-clave-99', $cuenta->password));
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/admin/entities', $this->datosEntidad());
        $entidad = Entidad::firstOrFail();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/entities/{$entidad->id}", $this->datosEntidad(['password' => '']))
            ->assertOk();

        $this->assertTrue(Hash::check('clave-segura-1', $entidad->usuarios()->firstOrFail()->password));
    }

    public function test_eliminar_entidad_desasigna_reportes_y_desactiva_sus_cuentas(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/admin/entities', $this->datosEntidad());
        $entidad = Entidad::firstOrFail();
        $reporte = Reporte::factory()->create(['id_entidad' => $entidad->id]);

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/entities/{$entidad->id}")->assertOk();

        $this->assertNull($reporte->fresh()->id_entidad);
        $this->assertSame('inactivo', User::where('email', 'acueducto@example.com')->first()->estado->value);
    }
}
