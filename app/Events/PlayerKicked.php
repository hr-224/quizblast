<?php
namespace App\Events;

use App\Models\Game;
use App\Models\GamePlayer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerKicked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game, public int $playerId) {}

    public function broadcastOn(): array
    {
        return [new Channel('game.' . $this->game->pin)];
    }

    public function broadcastAs(): string { return 'player-kicked'; }

    public function broadcastWith(): array
    {
        return [
            'player_id' => $this->playerId,
            'count'     => $this->game->players()->count(),
            'players'   => $this->game->players()->get(['id','nickname'])->toArray(),
        ];
    }
}
