<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameAnswer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $quizzes     = $request->user()->quizzes()->withCount('questions')->latest()->paginate(20);
        $recentGames = $request->user()->games()->with('quiz')->latest()->take(10)->get();
        return view('dashboard.index', compact('quizzes', 'recentGames'));
    }

    public function gameHistory(Game $game)
    {
        if ($game->user_id !== auth()->id()) abort(403);

        $game->load('quiz.questions.answers', 'players');
        $players = $game->players()->where('is_spectator', false)->orderByDesc('score')->get();

        // Build per-question answer breakdown
        // Load all game answers for this game upfront — avoids N+1 per question
        $allGameAnswers = GameAnswer::where('game_id', $game->id)
            ->with(['answer', 'player'])
            ->get()
            ->groupBy('question_id');

        $breakdown = [];
        foreach ($game->quiz->questions as $question) {
            $answers = $allGameAnswers->get($question->id, collect());

            $correctCount = 0;
            $playerBreakdown = [];

            foreach ($players as $player) {
                $playerAnswers = $answers->where('game_player_id', $player->id);
                $isCorrect     = false;

                if ($question->multiple_correct) {
                    $correctIds  = $question->answers->where('is_correct', true)->pluck('id')->toArray();
                    $selectedIds = $playerAnswers->pluck('answer_id')->toArray();
                    $isCorrect   = empty(array_diff($correctIds, $selectedIds)) && empty(array_diff($selectedIds, $correctIds));
                } else {
                    $isCorrect = $playerAnswers->first()?->answer?->is_correct ?? false;
                }

                if ($isCorrect) $correctCount++;
                $playerBreakdown[$player->id] = [
                    'correct'   => $isCorrect,
                    'points'    => $playerAnswers->sum('points_earned'),
                    'time_ms'   => $playerAnswers->first()?->response_time_ms ?? null,
                    'power_up'  => $playerAnswers->first()?->power_up_used,
                ];
            }

            $breakdown[] = [
                'question'        => $question,
                'correct_count'   => $correctCount,
                'total_players'   => $players->count(),
                'player_answers'  => $playerBreakdown,
            ];
        }

        return view('dashboard.history', compact('game', 'players', 'breakdown'));
    }
}
