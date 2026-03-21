<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PowerUpUsed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $pin,
        public string $type,
        public string $nickname,
        public int    $questionId
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('game.' . $this->pin)];
    }

    public function broadcastAs(): string { return 'power-up-used'; }

    public function broadcastWith(): array
    {
        return [
            'type'        => $this->type,
            'nickname'    => $this->nickname,
            'question_id' => $this->questionId,
        ];
    }
}
