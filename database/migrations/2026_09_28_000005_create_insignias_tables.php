<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Insignias (gamificación) y las que ha obtenido cada usuario.
     */
    public function up(): void
    {
        Schema::create('insignias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->string('icono')->nullable();
            $table->string('requisito_texto')->nullable();
        });

        Schema::create('insignias_usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('id_insignia')->constrained('insignias')->cascadeOnDelete();
            $table->timestampTz('fecha_obtencion')->nullable();

            $table->unique(['id_usuario', 'id_insignia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insignias_usuarios');
        Schema::dropIfExists('insignias');
    }
};
