<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameAnswer extends Model
{
    protected $fillable = ['game_id', 'game_player_id', 'question_id', 'answer_id', 'response_time_ms', 'points_earned', 'power_up_used', 'streak_bonus'];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(GamePlayer::class, 'game_player_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}
