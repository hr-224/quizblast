<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = ['quiz_id', 'user_id', 'pin', 'status', 'current_question', 'question_started_at'];

    protected $casts = ['question_started_at' => 'datetime'];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function players()
    {
        return $this->hasMany(GamePlayer::class)->orderByDesc('score');
    }

    public function gameAnswers()
    {
        return $this->hasMany(GameAnswer::class);
    }

    public function currentQuestion()
    {
        return $this->quiz->questions[$this->current_question] ?? null;
    }

    public function timeRemaining(): int
    {
        if (!$this->question_started_at || $this->status !== 'question') {
            return 0;
        }
        $question = $this->currentQuestion();
        if (!$question) return 0;
        $elapsed = now()->diffInSeconds($this->question_started_at, false);
        return max(0, $question->time_limit - $elapsed);
    }
}
