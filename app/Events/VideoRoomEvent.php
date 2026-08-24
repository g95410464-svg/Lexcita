<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario};
use Illuminate\Broadcasting\{Channel, InteractsWithSockets, PrivateChannel};
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Clase base para eventos de sala de video.
 * Todos los eventos de señalización WebRTC heredan de esta.
 */
abstract class VideoRoomEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VideoRoom $room,
        public Usuario $user,
        public array $payload = []
    ) {}

    /**
     * Canal privado por room_token.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("private-video-room.{$this->room->room_token}");
    }

    /**
     * Nombre del evento para el frontend (ej: 'webrtc.offer', 'chat.message').
     */
    abstract public function broadcastAs(): string;

    /**
     * Datos a transmitir. No incluir el stream de video/audio.
     */
    public function broadcastWith(): array
    {
        return array_merge([
            'user_id' => $this->user->id,
            'user_nombre' => $this->user->nombre,
            'user_rol' => $this->user->rol,
            'room_token' => $this->room->room_token,
        ], $this->payload);
    }

    /**
     * Solo el emisor y los suscriptores autorizados reciben el evento.
     */
    public function broadcastWithNoUser(): bool
    {
        return false;
    }
}