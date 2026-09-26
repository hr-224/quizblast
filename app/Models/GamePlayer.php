<?php

namespace App\Models;

use App\Events\PlayerLeft;
use Illuminate\Database\Eloquent\Model;

class GamePlayer extends Model
{
    protected $fillable = [
        'game_id','nickname','score','session_id','last_seen_at',
        'team','streak','best_streak','power_ups','is_spectator','player_account_id'
    ];

    protected $casts = [
        'power_ups'   => 'array',
        'is_spectator'=> 'boolean',
        'last_seen_at'=> 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function answers()
    {
        return $this->hasMany(GameAnswer::class);
    }

    public function playerAccount()
    {
        return $this->belongsTo(PlayerAccount::class);
    }

    public function getAvailablePowerUps(): array
    {
        $used = $this->power_ups ?? [];
        $all  = ['double_points', 'fifty_fifty', 'spy'];
        return array_values(array_diff($all, $used));
    }

    public function hasPowerUp(string $type): bool
    {
        return in_array($type, $this->getAvailablePowerUps());
    }

    public static function removeStale(int $gameId): void
    {
        $stale = static::where('game_id', $gameId)
            ->where('last_seen_at', '<', now()->subSeconds(20))
            ->whereNotNull('last_seen_at')
            ->where('is_spectator', false)
            ->get();

        if ($stale->isEmpty()) return;

        $game = \App\Models\Game::find($gameId);
        foreach ($stale as $player) {
            $player->delete();
        }
        if ($game) broadcast(new PlayerLeft($game));
    }
}
