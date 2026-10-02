<?php

namespace Database\Seeders;

use App\Models\CategoriaReporte;
use App\Models\Entidad;
use App\Models\Noticia;
use App\Models\Reporte;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración para desarrollo local: servicios en el mapa,
 * noticias y algunos reportes del ciudadano de prueba.
 */
class DatosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $servicios = [
            ['nombre' => 'Hospital Departamental de Buenaventura', 'tipo' => 'salud', 'latitud' => 3.8835, 'longitud' => -77.0180, 'direccion' => 'Barrio La Independencia', 'horario' => '24 horas', 'telefono' => '6022434000'],
            ['nombre' => 'Estación de Policía Centro', 'tipo' => 'seguridad', 'latitud' => 3.8870, 'longitud' => -77.0700, 'direccion' => 'Calle 1 con Carrera 3', 'horario' => '24 horas', 'telefono' => '123'],
            ['nombre' => 'Terminal de Transportes', 'tipo' => 'transporte', 'latitud' => 3.8790, 'longitud' => -77.0270, 'direccion' => 'Avenida Simón Bolívar', 'horario' => '5:00 a. m. - 10:00 p. m.'],
            ['nombre' => 'Parque Néstor Urbano Tenorio', 'tipo' => 'recreacion', 'latitud' => 3.8880, 'longitud' => -77.0720, 'direccion' => 'Zona Centro', 'horario' => 'Todo el día'],
            ['nombre' => 'Alcaldía Distrital', 'tipo' => 'administrativo', 'latitud' => 3.8860, 'longitud' => -77.0690, 'direccion' => 'Centro Administrativo Distrital', 'horario' => 'Lunes a viernes, 8:00 a. m. - 5:00 p. m.', 'telefono' => '6022410000'],
        ];

        foreach ($servicios as $servicio) {
            Servicio::updateOrCreate(['nombre' => $servicio['nombre']], $servicio);
        }

        $acueducto = Entidad::where('slug', 'acueducto')->first();

        Noticia::updateOrCreate(['titulo' => 'Mantenimiento programado de la red de acueducto'], [
            'contenido' => 'Este sábado se realizarán trabajos de mantenimiento en la red principal. Algunos sectores del centro tendrán suspensión del servicio entre las 8:00 a. m. y las 2:00 p. m.',
            'categoria' => 'servicios-publicos',
            'id_entidad' => $acueducto?->id,
            'esta_publicada' => true,
            'fecha_publicacion' => now()->subDay(),
        ]);

        Noticia::updateOrCreate(['titulo' => 'Nueva jornada de limpieza en el malecón'], [
            'contenido' => 'Invitamos a toda la comunidad a participar en la jornada de limpieza del malecón Bahía de la Cruz este domingo desde las 7:00 a. m.',
            'categoria' => 'ambiente',
            'id_entidad' => Entidad::where('slug', 'aseo')->value('id'),
            'esta_publicada' => true,
            'fecha_publicacion' => now()->subDays(3),
        ]);

        $ciudadano = User::where('email', 'ciudadano@buenaventura.local')->first();
        if (! $ciudadano || $ciudadano->reportes()->exists()) {
            return;
        }

        $reportes = [
            ['titulo' => 'Poste de luz apagado', 'categoria' => 'Luminaria dañada', 'descripcion' => 'El poste frente a la tienda lleva una semana sin funcionar y la calle queda muy oscura.', 'latitud' => 3.8820, 'longitud' => -77.0300],
            ['titulo' => 'Acumulación de basura', 'categoria' => 'Basura en vía pública', 'descripcion' => 'Bolsas de basura acumuladas en la esquina desde hace varios días.', 'latitud' => 3.8850, 'longitud' => -77.0650],
            ['titulo' => 'Tubo roto en la calle', 'categoria' => 'Fuga de agua', 'descripcion' => 'Sale agua constantemente de un tubo roto en la acera.', 'latitud' => 3.8780, 'longitud' => -77.0350],
        ];

        foreach ($reportes as $datos) {
            $categoria = CategoriaReporte::where('nombre', $datos['categoria'])->first();
            $reporte = new Reporte([...$datos, 'direccion_ubicacion' => "{$datos['latitud']}, {$datos['longitud']}", 'id_entidad' => $categoria?->id_entidad]);
            $reporte->id_usuario = $ciudadano->id;
            $reporte->save();
        }

        $ciudadano->forceFill(['reportes_creados' => count($reportes)])->save();
    }
}
