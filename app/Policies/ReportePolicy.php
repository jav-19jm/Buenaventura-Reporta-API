<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Reporte;
use App\Models\User;

class ReportePolicy
{
    /** Solo el autor gestiona su reporte (imagen, eliminación) */
    public function gestionar(User $user, Reporte $reporte): bool
    {
        return $reporte->id_usuario === $user->id;
    }

    /** Cambiar el estado: administración o la entidad asignada al reporte */
    public function cambiarEstado(User $user, Reporte $reporte): bool
    {
        return $user->tieneRol(RolUsuario::Administrador, RolUsuario::Moderador)
            || ($user->tieneRol(RolUsuario::Entidad) && $user->id_entidad !== null && $user->id_entidad === $reporte->id_entidad);
    }

    /**
     * Chat de seguimiento: autor, administración y la entidad asignada.
     */
    public function participarEnChat(User $user, Reporte $reporte): bool
    {
        return $reporte->id_usuario === $user->id
            || $user->tieneRol(RolUsuario::Administrador, RolUsuario::Moderador)
            || ($user->tieneRol(RolUsuario::Entidad) && $user->id_entidad !== null && $user->id_entidad === $reporte->id_entidad);
    }
}
