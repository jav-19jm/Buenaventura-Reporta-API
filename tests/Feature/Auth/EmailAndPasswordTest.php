<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\RestablecerPassword;
use App\Notifications\VerificarCorreo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Verificación de correo y recuperación de contraseña.
 */
class EmailAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function enlaceDeVerificacion(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }

    public function test_enlace_de_verificacion_marca_el_correo_y_redirige_al_login(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->enlaceDeVerificacion($user))
            ->assertRedirect('http://localhost:5173/login?verificado=ok');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_enlace_de_verificacion_alterado_no_verifica(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->enlaceDeVerificacion($user).'x')
            ->assertRedirect('http://localhost:5173/login?verificado=invalido');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_olvide_mi_contrasena_envia_enlace_al_frontend(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'ana@example.com'])->assertOk();

        Notification::assertSentTo($user, RestablecerPassword::class, function (RestablecerPassword $notificacion) use ($user) {
            return str_starts_with($notificacion->frontendUrl($user), 'http://localhost:5173/reset-password?token=');
        });
    }

    public function test_olvide_mi_contrasena_no_revela_si_el_correo_existe(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'nadie@example.com'])->assertOk();
    }

    public function test_restablecer_contrasena_con_token_valido(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'ana@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'nueva-clave-123',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('nueva-clave-123', $user->password));
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_las_plantillas_de_correo_incluyen_el_enlace(): void
    {
        $user = User::factory()->unverified()->create();

        $verificacion = (string) (new VerificarCorreo)->toMail($user)->render();
        $this->assertStringContainsString('/api/auth/verify-email/'.$user->id, $verificacion);

        $recuperacion = new RestablecerPassword('token-de-prueba');
        $html = (string) $recuperacion->toMail($user)->render();
        $this->assertStringContainsString(e($recuperacion->frontendUrl($user)), $html);
    }

    public function test_restablecer_contrasena_con_token_invalido_responde_422(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/reset-password', [
            'token' => 'token-falso',
            'email' => 'ana@example.com',
            'password' => 'nueva-clave-123',
        ])->assertUnprocessable();
    }
}
