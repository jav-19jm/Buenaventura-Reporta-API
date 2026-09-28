<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_correcto_devuelve_token_y_perfil(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ana@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'expires_in', 'user' => ['id', 'email', 'nombre_completo', 'rol', 'estado']])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.rol', 'ciudadano')
            ->assertJsonMissingPath('user.password');
    }

    public function test_login_ignora_mayusculas_en_el_correo(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/login', ['email' => '  ANA@Example.com ', 'password' => 'password'])
            ->assertOk();
    }

    public function test_login_con_contrasena_incorrecta_responde_401(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'otra-clave'])
            ->assertUnauthorized()
            ->assertJsonPath('codigo', 'credenciales_invalidas');
    }

    public function test_login_con_correo_no_verificado_responde_403(): void
    {
        User::factory()->unverified()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('codigo', 'correo_no_verificado')
            ->assertJsonMissingPath('token');
    }

    public function test_login_de_cuenta_suspendida_responde_403_con_motivo(): void
    {
        User::factory()->suspendido('Publicó reportes falsos.')->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('codigo', 'cuenta_inactiva')
            ->assertJsonPath('motivo_bloqueo', 'Publicó reportes falsos.')
            ->assertJsonMissingPath('token');
    }

    public function test_login_exige_correo_y_contrasena(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
