<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Nueva notificación para un usuario. Se emite en su canal privado
 * "usuario.{id}" y la campana del frontend la muestra sin recargar.
 */
class NotificacionCreada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $notificacion  Fila tal como se guardó en "notificaciones"
     */
    public function __construct(public array $notificacion) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('usuario.'.$this->notificacion['id_usuario']);
    }

    public function broadcastAs(): string
    {
        return 'notificacion.creada';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->notificacion;
    }
}
