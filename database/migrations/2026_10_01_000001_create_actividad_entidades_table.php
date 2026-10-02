<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de auditoría del panel institucional (inicios de sesión,
     * cambios de estado, actualizaciones del perfil de la entidad...).
     */
    public function up(): void
    {
        Schema::create('actividad_entidades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_entidad')->constrained('entidades')->cascadeOnDelete();
            $table->string('tipo_accion', 50);
            $table->string('titulo');
            $table->text('descripcion');
            $table->timestampTz('fecha_creacion')->nullable();

            $table->index(['id_entidad', 'fecha_creacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_entidades');
    }
};
