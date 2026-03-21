<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = ['user_id','title','description','is_public','category','tags'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function duplicate(): self
    {
        $new = $this->replicate(['is_public']);
        $new->title     = $this->title . ' (Copy)';
        $new->is_public = false;
        $new->save();

        foreach ($this->questions()->with('answers')->get() as $q) {
            $newQ = $q->replicate();
            $newQ->quiz_id = $new->id;
            $newQ->save();
            foreach ($q->answers as $a) {
                $newA = $a->replicate();
                $newA->question_id = $newQ->id;
                $newA->save();
            }
        }

        return $new;
    }
}
