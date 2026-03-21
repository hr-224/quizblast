<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStateChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game) {}

    public function broadcastOn(): array
    {
        return [new Channel('game.' . $this->game->pin)];
    }

    public function broadcastAs(): string
    {
        return 'game-state-changed';
    }

    public function broadcastWith(): array
    {
        $this->game->load('quiz.questions.answers', 'players');
        $question = $this->game->currentQuestion();

        $data = [
            'status'           => $this->game->status,
            'current_question' => $this->game->current_question,
            'total_questions'  => $this->game->quiz->questions->count(),
            'time_remaining'   => $this->game->timeRemaining(),
            'player_count'     => $this->game->players->count(),
        ];

        if ($question && in_array($this->game->status, ['question', 'reviewing'])) {
            $data['question'] = [
                'id'         => $question->id,
                'text'       => $question->question_text,
                'time_limit' => $question->time_limit,
                'points'     => $question->points,
                'answers'    => $question->answers->map(fn($a) => [
                    'id'         => $a->id,
                    'text'       => $a->answer_text,
                    'is_correct' => $this->game->status === 'reviewing' ? $a->is_correct : null,
                ]),
            ];
        }

        if (in_array($this->game->status, ['reviewing', 'finished'])) {
            $data['leaderboard'] = $this->game->players()
                ->orderByDesc('score')
                ->take(5)
                ->get(['nickname', 'score'])
                ->toArray();
        }

        return $data;
    }
}
