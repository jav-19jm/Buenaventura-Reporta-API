<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notificaciones internas de la plataforma (campana del panel).
     */
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('id_reporte')->nullable()->constrained('reportes')->cascadeOnDelete();
            $table->enum('tipo', ['reporte_actualizado', 'nuevo_mensaje', 'reporte_resuelto', 'mencion', 'alerta_sistema'])->nullable();
            $table->string('titulo');
            $table->text('mensaje');
            $table->boolean('esta_leida')->default(false);
            $table->timestampTz('fecha_creacion')->nullable();

            $table->index(['id_usuario', 'fecha_creacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
