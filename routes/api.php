<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/prueba', function () {
    return response()->json([
        'mensaje' => 'API de Buenaventura Reporta funcionando correctamente.',
    ]);
});

// ==========================================
// AUTENTICACIÓN (JWT)
// ==========================================
Route::prefix('auth')->group(function () {
    // Públicas (con límite de intentos por minuto)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    Route::middleware('throttle:5,1')->group(function () {
        Route::post('forgot-password', [PasswordResetController::class, 'forgot']);
        Route::post('reset-password', [PasswordResetController::class, 'reset']);
        Route::post('email/resend', [EmailVerificationController::class, 'resend']);
    });

    // Enlace firmado que llega en el correo de confirmación
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('verification.verify');

    // Requieren token válido y cuenta activa
    Route::middleware(['auth:api', 'activo'])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
