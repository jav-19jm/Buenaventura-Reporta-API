<?php

namespace Tests\Feature\PanelCiudadano;

use App\Models\CategoriaReporte;
use App\Models\Entidad;
use App\Models\Noticia;
use App\Models\Servicio;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_categorias_solo_activas(): void
    {
        CategoriaReporte::factory()->create(['nombre' => 'Activa']);
        CategoriaReporte::factory()->create(['nombre' => 'Inactiva', 'esta_activa' => false]);

        $this->getJson('/api/report-categories')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.nombre', 'Activa');
    }

    public function test_noticias_solo_publicadas_con_su_entidad(): void
    {
        $entidad = Entidad::factory()->create();
        Noticia::factory()->create(['titulo' => 'Publicada', 'id_entidad' => $entidad->id]);
        Noticia::factory()->borrador()->create();

        $this->getJson('/api/news')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.titulo', 'Publicada')
            ->assertJsonPath('0.entidades.nombre', $entidad->nombre);
    }

    public function test_servicios_y_entidades_solo_activos(): void
    {
        Servicio::factory()->create();
        Servicio::factory()->create(['esta_activo' => false]);
        Entidad::factory()->create();
        Entidad::factory()->create(['esta_activa' => false]);

        $this->getJson('/api/services')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/entities')->assertOk()->assertJsonCount(1);
    }

    public function test_el_seeder_de_catalogo_es_idempotente(): void
    {
        $this->seed(CatalogoSeeder::class);
        $this->seed(CatalogoSeeder::class);

        $this->assertSame(6, Entidad::count());
        $this->assertSame(7, CategoriaReporte::count());
        $this->assertNotNull(CategoriaReporte::where('nombre', 'Fuga de agua')->value('id_entidad'));
    }
}
