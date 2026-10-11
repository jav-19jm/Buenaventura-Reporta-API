<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso masivo (nueva noticia, nuevo servicio). En lugar de un evento por
 * cada ciudadano se emite uno solo en el canal público "avisos"; cada
 * cliente conectado recarga sus notificaciones al recibirlo.
 */
class AvisoGeneral implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $titulo, public string $mensaje) {}

    public function broadcastOn(): Channel
    {
        return new Channel('avisos');
    }

    public function broadcastAs(): string
    {
        return 'aviso.general';
    }
}
