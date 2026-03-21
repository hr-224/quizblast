<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnswerCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game, public int $questionId) {}

    public function broadcastOn(): array
    {
        return [new Channel('game.' . $this->game->pin)];
    }

    public function broadcastAs(): string
    {
        return 'answer-count-updated';
    }

    public function broadcastWith(): array
    {
        $question = $this->game->quiz->questions()->with('answers')->find($this->questionId);
        $counts   = [];

        foreach ($question->answers as $ans) {
            $counts[$ans->id] = $this->game->gameAnswers()
                ->where('question_id', $this->questionId)
                ->where('answer_id', $ans->id)
                ->count();
        }

        return [
            'question_id'   => $this->questionId,
            'answer_counts' => $counts,
            'total_answered' => $this->game->gameAnswers()
                ->where('question_id', $this->questionId)
                ->count(),
        ];
    }
}
