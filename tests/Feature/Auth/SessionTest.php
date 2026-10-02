<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Uso del token: /me, logout, refresh, bloqueo inmediato y restricción por rol.
 */
class SessionTest extends TestCase
{
    use RefreshDatabase;

    private function tokenPara(User $user): string
    {
        return auth('api')->login($user);
    }

    public function test_me_sin_token_responde_401(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_me_devuelve_el_perfil_del_usuario_autenticado(): void
    {
        $user = User::factory()->administrador()->create();

        $this->withToken($this->tokenPara($user))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('rol', 'administrador');
    }

    public function test_cuenta_suspendida_con_token_vigente_es_rechazada(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenPara($user);

        $user->forceFill(['estado' => 'suspendido', 'motivo_bloqueo' => 'Spam.'])->save();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertForbidden()
            ->assertJsonPath('codigo', 'cuenta_inactiva');
    }

    public function test_logout_invalida_el_token(): void
    {
        $token = $this->tokenPara(User::factory()->create());

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        auth('api')->forgetUser();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_refresh_entrega_un_token_nuevo(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenPara($user);

        $response = $this->withToken($token)->postJson('/api/auth/refresh');

        $response->assertOk()->assertJsonPath('user.id', $user->id);
        $this->assertNotSame($token, $response->json('token'));
    }

    public function test_middleware_de_rol_bloquea_a_otros_roles(): void
    {
        Route::middleware(['api', 'auth:api', EnsureUserHasRole::class.':administrador'])
            ->get('/api/_prueba-admin', fn () => response()->json(['ok' => true]));

        $this->withToken($this->tokenPara(User::factory()->create()))
            ->getJson('/api/_prueba-admin')
            ->assertForbidden();

        auth('api')->forgetUser();

        $this->withToken($this->tokenPara(User::factory()->administrador()->create()))
            ->getJson('/api/_prueba-admin')
            ->assertOk();
    }
}
