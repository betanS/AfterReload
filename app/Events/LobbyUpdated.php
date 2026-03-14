<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $serverId,
        public array $payload
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('lobby.' . $this->serverId);
    }

    public function broadcastAs(): string
    {
        return 'LobbyUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
