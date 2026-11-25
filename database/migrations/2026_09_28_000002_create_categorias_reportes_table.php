<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipos de incidencia que puede elegir el ciudadano. id_entidad es la
     * entidad responsable por defecto de esa categoría.
     */
    public function up(): void
    {
        Schema::create('categorias_reportes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            $table->string('nombre')->unique();
            $table->string('icono')->nullable();
            $table->string('color')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('esta_activa')->default(true);
            $table->timestampTz('fecha_creacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_reportes');
    }
};
