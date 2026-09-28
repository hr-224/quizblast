<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = ['user_id','title','description','is_public','category','tags','banner'];

    public const BANNER_DIR = 'uploads/banners';

    protected static function booted(): void
    {
        static::deleted(fn (Quiz $quiz) => static::deleteBannerFile($quiz->banner));
    }

    /** Public URL of the banner image, whether it is an uploaded file or a pasted URL. */
    public function getBannerUrlAttribute(): ?string
    {
        if (! $this->banner) {
            return null;
        }

        return preg_match('#^https?://#i', $this->banner) ? $this->banner : asset($this->banner);
    }

    /** Deletes an uploaded banner file once no quiz references it (duplicates share the file). */
    public static function deleteBannerFile(?string $path): void
    {
        if (! $path || ! str_starts_with($path, self::BANNER_DIR . '/') || str_contains($path, '..')) {
            return;
        }
        if (static::where('banner', $path)->exists()) {
            return;
        }

        @unlink(public_path($path));
    }

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
