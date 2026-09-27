<?php

namespace App\Events;

use App\Models\GamePlayer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GamePlayer $player) {}

    public function broadcastOn(): array
    {
        return [new Channel('game.' . $this->player->game->pin)];
    }

    public function broadcastAs(): string
    {
        return 'player-joined';
    }

    public function broadcastWith(): array
    {
        return [
            'id'       => $this->player->id,
            'nickname' => $this->player->nickname,
            'count'    => GamePlayer::where('game_id', $this->player->game_id)->where('is_spectator', false)->count(),
        ];
    }
}
