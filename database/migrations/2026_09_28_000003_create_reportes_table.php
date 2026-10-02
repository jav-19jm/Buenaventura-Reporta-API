<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reportes ciudadanos, sus votos, el chat de seguimiento y el historial de cambios.
     */
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion');
            // Nombre de la categoría (categorias_reportes.nombre), igual que en Supabase
            $table->string('categoria')->index();
            $table->string('direccion_ubicacion')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->text('url_imagen')->nullable();
            $table->enum('estado', ['pendiente', 'en_revision', 'en_proceso', 'resuelto', 'cancelado'])->default('pendiente')->index();
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->integer('votos_positivos')->default(0);
            $table->integer('votos_negativos')->default(0);
            $table->boolean('visto')->default(false);
            $table->boolean('visible')->default(true)->index();
            $table->timestampTz('fecha_creacion')->nullable();
            $table->timestampTz('fecha_actualizacion')->nullable();
        });

        Schema::create('votos_reportes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_reporte')->constrained('reportes')->cascadeOnDelete();
            $table->foreignUuid('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->enum('tipo_voto', ['voto_positivo', 'voto_negativo']);
            $table->timestampTz('fecha_creacion')->nullable();

            // Un voto por usuario y reporte (se puede cambiar, no duplicar)
            $table->unique(['id_reporte', 'id_usuario']);
        });

        Schema::create('mensajes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_reporte')->constrained('reportes')->cascadeOnDelete();
            $table->foreignUuid('id_remitente')->constrained('users')->cascadeOnDelete();
            $table->enum('tipo_remitente', ['usuario', 'entidad', 'moderador'])->default('usuario');
            $table->text('mensaje');
            $table->timestampTz('fecha_creacion')->nullable()->index();
        });

        Schema::create('historial_reportes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_reporte')->constrained('reportes')->cascadeOnDelete();
            $table->string('accion');
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->foreignUuid('id_usuario')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('fecha_creacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_reportes');
        Schema::dropIfExists('mensajes');
        Schema::dropIfExists('votos_reportes');
        Schema::dropIfExists('reportes');
    }
};
