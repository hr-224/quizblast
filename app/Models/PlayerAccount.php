<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlayerAccount extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name','email','password','total_score','games_played','wins'];
    protected $hidden   = ['password','remember_token'];
    protected $casts    = ['password' => 'hashed'];

    public function gamePlayers()
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function getWinRateAttribute(): string
    {
        if ($this->games_played === 0) return '0%';
        return round(($this->wins / $this->games_played) * 100) . '%';
    }

    public function getRecentGames(): \Illuminate\Support\Collection
    {
        return $this->gamePlayers()
            ->with(['game.quiz'])
            ->latest()
            ->take(10)
            ->get();
    }
}
