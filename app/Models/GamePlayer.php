<?php

namespace App\Models;

use App\Events\PlayerLeft;
use Illuminate\Database\Eloquent\Model;

class GamePlayer extends Model
{
    protected $fillable = [
        'game_id','nickname','score','session_id','last_seen_at',
        'team','streak','best_streak','power_ups','is_spectator','user_id','stats_credited_at'
    ];

    protected $casts = [
        'power_ups'         => 'array',
        'is_spectator'      => 'boolean',
        'last_seen_at'      => 'datetime',
        'stats_credited_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function answers()
    {
        return $this->hasMany(GameAnswer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
        $game = \App\Models\Game::find($gameId);
        // Only prune no-shows while the game is still in the lobby. Once it has started,
        // a phone that goes idle (screen lock, a backgrounded tab during the teacher's
        // explanation) keeps its row and score — auto-removing it mid-game would silently
        // drop the student, and their next answer would fail with "Time's up!".
        if (!$game || $game->status !== 'waiting') return;

        $stale = static::where('game_id', $gameId)
            ->where('last_seen_at', '<', now()->subSeconds(20))
            ->whereNotNull('last_seen_at')
            ->where('is_spectator', false)
            ->get();

        if ($stale->isEmpty()) return;

        foreach ($stale as $player) {
            $player->delete();
        }
        broadcast(new PlayerLeft($game));
    }
}
