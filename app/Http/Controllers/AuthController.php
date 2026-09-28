<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Registro de ciudadanos. El rol siempre es "ciudadano" y la cuenta queda
     * pendiente de verificar el correo antes de poder iniciar sesión.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Ya existe una cuenta registrada con este correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $user = User::create($validated);
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registro exitoso. Revisa tu correo electrónico para activar tu cuenta.',
            'user' => $user,
        ], 201);
    }

    /**
     * Inicio de sesión: valida credenciales, correo verificado y estado de la cuenta.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $user = User::where('email', mb_strtolower(trim($credentials['email'])))->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas. Verifica tu correo y contraseña.',
                'codigo' => 'credenciales_invalidas',
            ], 401);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Tu correo electrónico aún no ha sido verificado. Revisa tu bandeja de entrada.',
                'codigo' => 'correo_no_verificado',
            ], 403);
        }

        if (! $user->estaActivo()) {
            return self::respuestaCuentaInactiva($user);
        }

        return $this->respondWithToken(auth('api')->login($user), $user);
    }

    /**
     * Usuario autenticado con su perfil completo.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * Cierra la sesión invalidando el token actual.
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Renueva el token. Acepta tokens ya expirados mientras estén dentro de
     * JWT_REFRESH_TTL, por eso la ruta no usa el middleware auth:api.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
        } catch (JWTException) {
            return response()->json(['message' => 'La sesión expiró. Inicia sesión nuevamente.'], 401);
        }

        $user = auth('api')->setToken($token)->user();

        if (! $user) {
            return response()->json(['message' => 'La sesión expiró. Inicia sesión nuevamente.'], 401);
        }

        if (! $user->estaActivo()) {
            auth('api')->invalidate();

            return self::respuestaCuentaInactiva($user);
        }

        return $this->respondWithToken($token, $user);
    }

    /**
     * Respuesta común para cuentas suspendidas o inactivas (login, refresh y middleware "activo").
     */
    public static function respuestaCuentaInactiva(User $user): JsonResponse
    {
        $estado = $user->estado->value === 'suspendido' ? 'suspendida' : 'inactiva';
        $motivo = $user->motivo_bloqueo ?: 'No se especificó un motivo.';

        return response()->json([
            'message' => "Tu cuenta está {$estado}. Motivo: {$motivo} Si crees que es un error, contacta a los administradores.",
            'codigo' => 'cuenta_inactiva',
            'estado' => $user->estado->value,
            'motivo_bloqueo' => $user->motivo_bloqueo,
        ], 403);
    }

    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }
}
