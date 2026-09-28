<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Notifications\RestablecerPassword;
use App\Notifications\VerificarCorreo;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Usuario de la plataforma (equivale a auth.users + perfiles de Supabase).
 */
class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_actualizacion';

    /**
     * Valores por defecto (coinciden con los de la migración) para que el
     * modelo recién creado se serialice completo sin volver a consultarlo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'rol' => 'ciudadano',
        'estado' => 'activo',
        'puntuacion_reputacion' => 0,
        'votos_positivos' => 0,
        'votos_negativos' => 0,
        'reportes_creados' => 0,
        'reportes_resueltos' => 0,
    ];

    /**
     * Rol, estado, contadores e id_entidad no son asignables en masa:
     * solo cambian mediante acciones explícitas del backend.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre_completo',
        'email',
        'password',
        'telefono',
        'url_avatar',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => RolUsuario::class,
            'estado' => EstadoUsuario::class,
            'puntuacion_reputacion' => 'integer',
            'votos_positivos' => 'integer',
            'votos_negativos' => 'integer',
            'reportes_creados' => 'integer',
            'reportes_resueltos' => 'integer',
        ];
    }

    /** Los correos se guardan siempre en minúsculas para evitar duplicados */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => mb_strtolower(trim($value)));
    }

    public function estaActivo(): bool
    {
        return $this->estado === EstadoUsuario::Activo;
    }

    public function tieneRol(RolUsuario|string ...$roles): bool
    {
        $valores = array_map(fn ($rol) => $rol instanceof RolUsuario ? $rol->value : $rol, $roles);

        return in_array($this->rol->value, $valores, true);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerificarCorreo);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerPassword($token));
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Claims adicionales del JWT (informativos: la autorización se valida siempre contra la BD).
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return ['rol' => $this->rol->value];
    }
}
