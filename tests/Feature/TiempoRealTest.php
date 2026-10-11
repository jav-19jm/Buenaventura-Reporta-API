<?php

namespace Tests\Feature;

use App\Enums\TipoNotificacion;
use App\Events\AvisoGeneral;
use App\Events\ChatActualizado;
use App\Events\NotificacionCreada;
use App\Models\Reporte;
use App\Models\Servicio;
use App\Models\User;
use App\Services\Notificador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * WebSockets (Reverb), caché de catálogos y colas.
 */
class TiempoRealTest extends TestCase
{
    use RefreshDatabase;

    /** Usa el driver de Reverb con claves de prueba para ejecutar la autorización real de canales */
    private function usarReverb(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'clave-prueba',
            'broadcasting.connections.reverb.secret' => 'secreto-prueba',
            'broadcasting.connections.reverb.app_id' => 'app-prueba',
        ]);

        // Los canales se registraron al arrancar sobre el driver "null" de las pruebas;
        // se vuelven a registrar sobre el driver de Reverb recién configurado.
        require base_path('routes/channels.php');
    }

    public function test_enviar_un_mensaje_emite_el_evento_del_chat(): void
    {
        Event::fake([ChatActualizado::class]);
        $reporte = Reporte::factory()->create();

        $this->actingAs($reporte->usuario, 'api')
            ->postJson("/api/reports/{$reporte->id}/messages", ['mensaje' => 'Hola'])
            ->assertCreated();

        Event::assertDispatched(ChatActualizado::class, fn (ChatActualizado $e) => $e->idReporte === $reporte->id
            && $e->broadcastOn()->name === "private-reporte.{$reporte->id}");
    }

    public function test_notificaciones_individuales_y_aviso_general_para_envios_masivos(): void
    {
        Event::fake([NotificacionCreada::class, AvisoGeneral::class]);
        $notificador = app(Notificador::class);

        $pocos = User::factory()->count(2)->create()->pluck('id');
        $notificador->notificar($pocos, TipoNotificacion::Mencion, 'Hola', 'Mensaje');
        Event::assertDispatchedTimes(NotificacionCreada::class, 2);
        Event::assertNotDispatched(AvisoGeneral::class);

        $muchos = User::factory()->count(30)->create()->pluck('id');
        $notificador->notificar($muchos, TipoNotificacion::AlertaSistema, 'Nueva noticia', 'Texto');
        Event::assertDispatchedTimes(NotificacionCreada::class, 2);
        Event::assertDispatched(AvisoGeneral::class, fn (AvisoGeneral $e) => $e->titulo === 'Nueva noticia');
        $this->assertDatabaseCount('notificaciones', 32);
    }

    public function test_solo_el_dueno_puede_suscribirse_a_su_canal_de_notificaciones(): void
    {
        $this->usarReverb();
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-usuario.{$user->id}"])
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-usuario.{$user->id}"])
            ->assertForbidden();
    }

    public function test_canal_del_chat_restringido_a_los_participantes(): void
    {
        $this->usarReverb();
        $reporte = Reporte::factory()->create();

        $this->actingAs($reporte->usuario, 'api')
            ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-reporte.{$reporte->id}"])
            ->assertOk();

        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-reporte.{$reporte->id}"])
            ->assertForbidden();
    }

    public function test_la_autorizacion_de_canales_exige_token(): void
    {
        $this->usarReverb();

        $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-usuario.x'])
            ->assertUnauthorized();
    }

    public function test_la_cache_del_catalogo_se_invalida_al_modificar(): void
    {
        Servicio::factory()->create(['nombre' => 'Hospital']);
        $this->getJson('/api/services')->assertJsonCount(1);

        // Leído desde caché: un cambio sin pasar por el modelo no se ve...
        Servicio::query()->update(['nombre' => 'Cambiado por SQL']);
        $this->getJson('/api/services')->assertJsonPath('0.nombre', 'Hospital');

        // ...pero cualquier cambio a través del modelo (como hace la administración) la invalida
        Servicio::factory()->create(['nombre' => 'Estación de Bomberos']);
        $this->getJson('/api/services')->assertJsonCount(2);
    }
}
