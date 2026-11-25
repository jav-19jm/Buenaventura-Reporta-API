<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerificarCorreo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_crea_ciudadano_sin_verificar_y_envia_correo(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'nombre_completo' => 'Ana Pérez',
            'email' => 'Ana@Example.com',
            'password' => 'secreto123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'ana@example.com')
            ->assertJsonPath('user.rol', 'ciudadano')
            ->assertJsonPath('user.estado', 'activo')
            ->assertJsonPath('user.reportes_creados', 0)
            ->assertJsonMissingPath('token');

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerificarCorreo::class);
    }

    public function test_registro_no_permite_elegir_rol(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'nombre_completo' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'secreto123',
            'rol' => 'administrador',
            'estado' => 'activo',
        ])->assertCreated()->assertJsonPath('user.rol', 'ciudadano');
    }

    public function test_registro_rechaza_correo_duplicado(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/register', [
            'nombre_completo' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'secreto123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_registro_exige_contrasena_de_8_caracteres(): void
    {
        $this->postJson('/api/auth/register', [
            'nombre_completo' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'corta',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }
}
