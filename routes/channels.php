<?php

use App\Models\Reporte;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de WebSocket (Laravel Reverb)
|--------------------------------------------------------------------------
| Los canales privados se autorizan en POST /api/broadcasting/auth con el
| token JWT del usuario. El canal público "avisos" no requiere autorización.
*/

// Notificaciones personales (campana del panel)
Broadcast::channel('usuario.{id}', fn (User $user, string $id) => $user->id === $id);

// Chat de seguimiento de un reporte: mismos participantes que en ReportePolicy
Broadcast::channel('reporte.{reporte}', fn (User $user, Reporte $reporte) => $user->can('participarEnChat', $reporte));
