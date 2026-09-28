<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'quiz_id', 'question_text', 'image_url', 'video_url',
        'multiple_correct', 'time_limit', 'answer_delay', 'points', 'order',
    ];

    protected $casts = ['multiple_correct' => 'boolean'];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class)->orderBy('order');
    }

    public function gameAnswers()
    {
        return $this->hasMany(GameAnswer::class);
    }

    public function getYoutubeId(): ?string
    {
        if (! $this->video_url) {
            return null;
        }
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->video_url, $m);

        return $m[1] ?? null;
    }
}
