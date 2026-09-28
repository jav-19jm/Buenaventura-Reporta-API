<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Envía el correo de recuperación. La respuesta no revela si el correo existe.
     */
    public function forgot(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
        ]);

        $status = Password::sendResetLink(['email' => mb_strtolower(trim($validated['email']))]);

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => 'Ya solicitaste un enlace hace poco. Espera un momento antes de intentarlo de nuevo.',
            ], 429);
        }

        return response()->json([
            'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
        ]);
    }

    /**
     * Cambia la contraseña usando el token recibido por correo.
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'token.required' => 'El enlace de recuperación no es válido.',
            'email.required' => 'El enlace de recuperación no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $validated['email'] = mb_strtolower(trim($validated['email']));

        $status = Password::reset($validated, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));

            // Abrir el enlace del correo también demuestra que el correo le pertenece
            if (! $user->hasVerifiedEmail()) {
                $user->email_verified_at = now();
            }

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'El enlace de recuperación no es válido o ha expirado. Solicita uno nuevo.',
            ], 422);
        }

        return response()->json(['message' => 'Tu contraseña ha sido actualizada. Ya puedes iniciar sesión.']);
    }
}
