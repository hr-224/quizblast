<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameReaction extends Model
{
    protected $fillable = ['game_id','game_player_id','emoji'];

    public function game()   { return $this->belongsTo(Game::class); }
    public function player() { return $this->belongsTo(GamePlayer::class, 'game_player_id'); }
}
