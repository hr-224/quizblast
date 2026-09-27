<?php
namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerLeft implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game) {}

    public function broadcastOn(): array
    {
        return [new Channel('game.' . $this->game->pin)];
    }

    public function broadcastAs(): string { return 'player-left'; }

    public function broadcastWith(): array
    {
        return [
            'count'   => $this->game->players()->where('is_spectator', false)->count(),
            'players' => $this->game->players()->where('is_spectator', false)->get(['id','nickname'])->toArray(),
        ];
    }
}
