<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * El chat de un reporte cambió (mensaje creado, editado o eliminado).
 * Los participantes suscritos a "reporte.{id}" recargan los mensajes.
 */
class ChatActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $idReporte, public string $accion) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('reporte.'.$this->idReporte);
    }

    public function broadcastAs(): string
    {
        return 'chat.actualizado';
    }

    /** @return array<string, string> */
    public function broadcastWith(): array
    {
        return ['id_reporte' => $this->idReporte, 'accion' => $this->accion];
    }
}
