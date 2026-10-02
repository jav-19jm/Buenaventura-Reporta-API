<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * La tabla users reemplaza a auth.users + public.perfiles de Supabase:
     * mismas columnas y nombres que "perfiles" más las credenciales de acceso.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Datos de perfil
            $table->string('nombre_completo')->nullable();
            $table->string('telefono')->nullable();
            $table->text('url_avatar')->nullable();
            $table->enum('rol', ['ciudadano', 'entidad', 'moderador', 'administrador'])->default('ciudadano');
            $table->enum('estado', ['activo', 'inactivo', 'suspendido'])->default('activo');
            $table->text('motivo_bloqueo')->nullable();

            // Reputación y contadores
            $table->integer('puntuacion_reputacion')->default(0);
            $table->integer('votos_positivos')->default(0);
            $table->integer('votos_negativos')->default(0);
            $table->integer('reportes_creados')->default(0);
            $table->integer('reportes_resueltos')->default(0);

            // Entidad a la que pertenece una cuenta con rol "entidad".
            // La llave foránea se agrega en la migración de la tabla entidades.
            $table->uuid('id_entidad')->nullable()->index();

            $table->rememberToken();
            $table->timestampTz('fecha_creacion')->nullable();
            $table->timestampTz('fecha_actualizacion')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
