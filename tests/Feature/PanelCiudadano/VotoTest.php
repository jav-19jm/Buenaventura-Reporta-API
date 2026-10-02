<?php

namespace Tests\Feature\PanelCiudadano;

use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotoTest extends TestCase
{
    use RefreshDatabase;

    private function votar(User $user, Reporte $reporte, string $tipo)
    {
        return $this->actingAs($user, 'api')->postJson("/api/reports/{$reporte->id}/votes", ['tipo_voto' => $tipo]);
    }

    public function test_voto_actualiza_contadores_reputacion_y_notifica_al_autor(): void
    {
        $reporte = Reporte::factory()->create();
        $votante = User::factory()->create();

        $this->votar($votante, $reporte, 'voto_positivo')
            ->assertOk()
            ->assertJsonPath('votos_positivos', 1);

        $autor = $reporte->usuario->fresh();
        $this->assertSame(1, $autor->votos_positivos);
        $this->assertSame(1, $autor->puntuacion_reputacion);
        $this->assertDatabaseHas('notificaciones', ['id_usuario' => $autor->id, 'id_reporte' => $reporte->id, 'tipo' => 'mencion']);
    }

    public function test_repetir_el_mismo_voto_es_rechazado(): void
    {
        $reporte = Reporte::factory()->create();
        $votante = User::factory()->create();

        $this->votar($votante, $reporte, 'voto_positivo')->assertOk();
        $this->votar($votante, $reporte, 'voto_positivo')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tipo_voto');
    }

    public function test_cambiar_el_voto_recalcula_los_contadores(): void
    {
        $reporte = Reporte::factory()->create();
        $votante = User::factory()->create();

        $this->votar($votante, $reporte, 'voto_positivo');
        $this->votar($votante, $reporte, 'voto_negativo')
            ->assertOk()
            ->assertJsonPath('votos_positivos', 0)
            ->assertJsonPath('votos_negativos', 1);

        $this->assertSame(-1, $reporte->usuario->fresh()->puntuacion_reputacion);
        $this->assertDatabaseCount('votos_reportes', 1);
    }

    public function test_tipo_de_voto_invalido(): void
    {
        $this->votar(User::factory()->create(), Reporte::factory()->create(), 'me_encanta')
            ->assertUnprocessable();
    }
}
