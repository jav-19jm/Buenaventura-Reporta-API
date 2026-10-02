<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VotoController;
use Illuminate\Support\Facades\Route;

// Los identificadores son UUID: cualquier otro valor responde 404 sin llegar a la BD
$uuid = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
Route::pattern('reporte', $uuid);
Route::pattern('mensaje', $uuid);
Route::pattern('notificacion', $uuid);
Route::pattern('usuario', $uuid);

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

// ==========================================
// PANEL CIUDADANO
// ==========================================

// Lectura pública (mapa, landing y formularios)
Route::get('report-categories', [CatalogoController::class, 'categorias']);
Route::get('entities', [CatalogoController::class, 'entidades']);
Route::get('services', [CatalogoController::class, 'servicios']);
Route::get('news', [CatalogoController::class, 'noticias']);
Route::get('reports', [ReporteController::class, 'index']);
Route::get('reports/{reporte}', [ReporteController::class, 'show']);
Route::get('users/{usuario}', [UsuarioController::class, 'show']);
Route::get('users/{usuario}/badges', [UsuarioController::class, 'insignias']);

// Requieren sesión y cuenta activa
Route::middleware(['auth:api', 'activo'])->group(function () {
    // Reportes propios
    Route::get('users/me/reports', [ReporteController::class, 'misReportes']);
    Route::post('reports', [ReporteController::class, 'store'])->middleware('throttle:10,1');
    Route::delete('reports/{reporte}', [ReporteController::class, 'destroy']);
    Route::post('reports/{reporte}/image', [ReporteController::class, 'subirImagen']);
    Route::post('reports/{reporte}/votes', [VotoController::class, 'store'])->middleware('throttle:30,1');

    // Chat de seguimiento
    Route::get('reports/{reporte}/messages', [MensajeController::class, 'index']);
    Route::post('reports/{reporte}/messages', [MensajeController::class, 'store'])->middleware('throttle:20,1');
    Route::patch('messages/{mensaje}', [MensajeController::class, 'update']);
    Route::delete('messages/{mensaje}', [MensajeController::class, 'destroy']);

    // Notificaciones
    Route::get('users/me/notifications', [NotificacionController::class, 'index']);
    Route::patch('notifications/{notificacion}/read', [NotificacionController::class, 'marcarLeida']);
    Route::delete('notifications/{notificacion}', [NotificacionController::class, 'destroy']);

    // Perfil
    Route::post('users/me/avatar', [UsuarioController::class, 'subirAvatar']);
    Route::post('users/{usuario}/report-abuse', [UsuarioController::class, 'denunciar'])->middleware('throttle:5,1');
});
