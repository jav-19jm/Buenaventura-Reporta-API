<?php

use App\Http\Controllers\Admin\EntidadAdminController;
use App\Http\Controllers\Admin\EstadisticasController;
use App\Http\Controllers\Admin\NoticiaAdminController;
use App\Http\Controllers\Admin\ReporteAdminController;
use App\Http\Controllers\Admin\ServicioAdminController;
use App\Http\Controllers\Admin\UsuarioAdminController;
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
Route::pattern('entidad', $uuid);
Route::pattern('noticia', $uuid);
Route::pattern('servicio', $uuid);

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
    // Administración o entidad asignada
    Route::patch('reports/{reporte}/status', [ReporteController::class, 'cambiarEstado']);

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

// ==========================================
// PANEL DE ADMINISTRACIÓN
// ==========================================
Route::middleware(['auth:api', 'activo', 'rol:administrador'])->prefix('admin')->group(function () {
    Route::get('stats', EstadisticasController::class);

    // Usuarios
    Route::get('users', [UsuarioAdminController::class, 'index']);
    Route::patch('users/{usuario}/status', [UsuarioAdminController::class, 'cambiarEstado']);
    Route::patch('users/{usuario}/role', [UsuarioAdminController::class, 'cambiarRol']);

    // Reportes
    Route::get('reports', [ReporteAdminController::class, 'index']);
    Route::patch('reports/{reporte}/entity', [ReporteAdminController::class, 'asignarEntidad']);
    Route::delete('reports/{reporte}', [ReporteAdminController::class, 'destroy']);

    // Entidades (con su cuenta institucional)
    Route::get('entities', [EntidadAdminController::class, 'index']);
    Route::post('entities', [EntidadAdminController::class, 'store']);
    Route::put('entities/{entidad}', [EntidadAdminController::class, 'update']);
    Route::delete('entities/{entidad}', [EntidadAdminController::class, 'destroy']);

    // Noticias
    Route::get('news', [NoticiaAdminController::class, 'index']);
    Route::post('news', [NoticiaAdminController::class, 'store']);
    Route::put('news/{noticia}', [NoticiaAdminController::class, 'update']);
    Route::patch('news/{noticia}/publish', [NoticiaAdminController::class, 'cambiarPublicacion']);
    Route::post('news/{noticia}/image', [NoticiaAdminController::class, 'subirImagen']);
    Route::delete('news/{noticia}', [NoticiaAdminController::class, 'destroy']);

    // Servicios del mapa
    Route::get('services', [ServicioAdminController::class, 'index']);
    Route::post('services', [ServicioAdminController::class, 'store']);
    Route::put('services/{servicio}', [ServicioAdminController::class, 'update']);
    Route::delete('services/{servicio}', [ServicioAdminController::class, 'destroy']);
});
