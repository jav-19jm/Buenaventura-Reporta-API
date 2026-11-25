<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Noticias publicadas por la administración y servicios (puntos de interés) del mapa.
     */
    public function up(): void
    {
        Schema::create('noticias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            $table->string('titulo');
            $table->text('contenido');
            $table->text('url_imagen')->nullable();
            $table->string('categoria')->nullable();
            $table->boolean('esta_publicada')->default(false)->index();
            $table->timestampTz('fecha_publicacion')->nullable();
            $table->timestampTz('fecha_creacion')->nullable();
            $table->timestampTz('fecha_actualizacion')->nullable();
        });

        Schema::create('servicios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('tipo');
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->string('direccion')->nullable();
            $table->string('horario')->nullable();
            $table->string('telefono')->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->timestampTz('fecha_creacion')->nullable();
            $table->timestampTz('fecha_actualizacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('noticias');
    }
};
