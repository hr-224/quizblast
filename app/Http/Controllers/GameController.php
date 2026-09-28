<?php

namespace App\Http\Controllers;

use App\Events\GameStateChanged;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Quiz;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function start(Quiz $quiz)
    {
        if (! $quiz->is_public && $quiz->user_id !== auth()->id()) {
            abort(403);
        }
        if ($quiz->questions()->count() === 0) {
            return redirect()->route('quizzes.edit', $quiz)->with('error', 'Add at least one question before hosting.');
        }

        $game = null;
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            // Skip PINs already in active use
            if (Game::where('pin', $candidate)->where('status', '!=', 'finished')->exists()) {
                continue;
            }
            try {
                $game = Game::create([
                    'quiz_id' => $quiz->id,
                    'user_id' => auth()->id(),
                    'pin' => $candidate,
                    'status' => 'waiting',
                    'team_mode' => false,
                    'spectator_mode' => true,
                ]);
                break;
            } catch (UniqueConstraintViolationException $e) {
                // Concurrent request claimed this PIN — retry
                continue;
            }
        }

        if (! $game) {
            return redirect()->route('dashboard')->with('error', 'Could not generate a unique game PIN. Please try again.');
        }

        return redirect()->route('game.lobby', $game);
    }

    public function lobby(Game $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }
        $game->load('quiz', 'players');

        return view('host.lobby', compact('game'));
    }

    public function launch(Request $request, Game $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }
        if ($game->players()->where('is_spectator', false)->count() === 0) {
            return back()->with('error', 'Wait for at least one player to join.');
        }

        $game->update([
            'status' => 'question',
            'current_question' => 0,
            'question_started_at' => now(),
        ]);

        broadcast(new GameStateChanged($game->fresh()));

        return redirect()->route('game.question', $game);
    }

    public function showQuestion(Game $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }
        $game->load('quiz.questions.answers', 'players');
        $questions = $game->quiz->questions;
        $question = $questions[$game->current_question] ?? null;

        if (! $question) {
            return redirect()->route('game.final', $game);
        }

        $answerCounts = $game->gameAnswers()
            ->where('question_id', $question->id)
            ->selectRaw('answer_id, count(*) as total')
            ->groupBy('answer_id')
            ->pluck('total', 'answer_id');

        $totalAnswered = $game->gameAnswers()->where('question_id', $question->id)->count();
        $totalPlayers = $game->players()->where('is_spectator', false)->count();

        return view('host.question', compact('game', 'question', 'questions', 'answerCounts', 'totalAnswered', 'totalPlayers'));
    }

    public function reveal(Game $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }
        $game->update(['status' => 'reviewing']);
        broadcast(new GameStateChanged($game->fresh()));

        return back();
    }

    public function skip(Game $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }

        return $this->advanceQuestion($game);
    }

    public function next(Game $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }

        return $this->advanceQuestion($game);
    }

    private function advanceQuestion(Game $game)
    {
        $total = $game->quiz->questions()->count();
        $next = $game->current_question + 1;

        if ($next >= $total) {
            $game->update(['status' => 'finished']);
            broadcast(new GameStateChanged($game->fresh()));

            return redirect()->route('game.final', $game);
        }

        $game->update([
            'status' => 'question',
            'current_question' => $next,
            'question_started_at' => now(),
        ]);

        broadcast(new GameStateChanged($game->fresh()));

        return redirect()->route('game.question', $game);
    }

    public function final(Game $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }
        $game->update(['status' => 'finished']);
        broadcast(new GameStateChanged($game->fresh()));
        $game->load('players', 'quiz');
        $players = $game->players()->where('is_spectator', false)->orderByDesc('score')->get();

        return view('host.final', compact('game', 'players'));
    }

    public function end(Game $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }
        $game->update(['status' => 'finished']);
        broadcast(new GameStateChanged($game->fresh()));

        return redirect()->route('dashboard')->with('success', 'Game ended.');
    }

    /** Lets a player whose rejoin token was lost back in by nickname, once, within 5 minutes. */
    public function releasePlayer(Game $game, GamePlayer $player)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }
        abort_unless($player->game_id === $game->id, 404);

        $player->update(['rejoin_released_until' => now()->addMinutes(5)]);

        return response()->json(['ok' => true]);
    }

    // ── API ──────────────────────────────────────────────────────────────────

    public function state(Request $request, string $pin)
    {
        $game = Game::where('pin', $pin)->first();
        if (! $game) {
            return response()->json(['error' => 'Game not found'], 404);
        }

        $game->load('quiz.questions.answers');
        $question = $game->currentQuestion();

        $data = [
            'status' => $game->status,
            'current_question' => $game->current_question,
            'total_questions' => $game->quiz->questions->count(),
            'time_remaining' => $game->timeRemaining(),
            'delay_remaining' => $game->delayRemaining(),
            'player_count' => $game->players()->where('is_spectator', false)->count(),
            'team_mode' => $game->team_mode,
        ];

        if ($question && in_array($game->status, ['question', 'reviewing'])) {
            $data['question'] = [
                'id' => $question->id,
                'text' => $question->question_text,
                'time_limit' => $question->time_limit,
                'answer_delay' => $question->answer_delay,
                'points' => $question->points,
                'image_url' => $question->image_url,
                'video_url' => $question->video_url,
                'youtube_id' => $question->getYoutubeId(),
                'multiple_correct' => $question->multiple_correct,
                'answers' => $question->answers->map(fn ($a) => [
                    'id' => $a->id,
                    'text' => $a->answer_text,
                    'is_correct' => $game->status === 'reviewing' ? $a->is_correct : null,
                ]),
            ];
        }

        if (in_array($game->status, ['reviewing', 'finished'])) {
            $data['leaderboard'] = $game->players()
                ->where('is_spectator', false)
                ->orderByDesc('score')
                ->take(10)
                ->get(['nickname', 'score', 'streak', 'best_streak'])
                ->toArray();
        }

        return response()->json($data);
    }

    public function players(string $pin)
    {
        $game = Game::where('pin', $pin)->first();
        if (! $game) {
            return response()->json(['error' => 'Game not found'], 404);
        }

        return response()->json([
            'players' => $game->players()->where('is_spectator', false)->orderBy('created_at')->get(['id', 'nickname', 'score', 'team', 'streak'])->toArray(),
            'count' => $game->players()->where('is_spectator', false)->count(),
            'status' => $game->status,
        ]);
    }
}
