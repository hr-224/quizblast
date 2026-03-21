<?php

namespace App\Events;

use App\Models\GameReaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmojiReacted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $pin,
        public string $emoji,
        public string $nickname
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('game.' . $this->pin)];
    }

    public function broadcastAs(): string { return 'emoji-reacted'; }

    public function broadcastWith(): array
    {
        return ['emoji' => $this->emoji, 'nickname' => $this->nickname];
    }
}
