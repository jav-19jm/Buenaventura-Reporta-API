<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entidades institucionales que atienden los reportes (Acueducto, Policía...).
     */
    public function up(): void
    {
        Schema::create('entidades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['servicios-publicos', 'seguridad', 'salud', 'infraestructura', 'ambiente', 'otro']);
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('color')->nullable();
            $table->text('logo_url')->nullable();
            $table->string('sitio_web')->nullable();
            $table->boolean('esta_activa')->default(true);
            $table->timestampTz('fecha_creacion')->nullable();
            $table->timestampTz('fecha_actualizacion')->nullable();
        });

        // Cuentas con rol "entidad" vinculadas a su institución
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_entidad')->references('id')->on('entidades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_entidad']);
        });

        Schema::dropIfExists('entidades');
    }
};
