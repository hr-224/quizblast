<?php

namespace App\Http\Controllers;

use App\Events\AnswerCountUpdated;
use App\Events\EmojiReacted;
use App\Events\PlayerJoined;
use App\Events\PlayerKicked;
use App\Events\PlayerLeft;
use App\Events\PowerUpUsed;
use App\Models\Game;
use App\Models\GameAnswer;
use App\Models\GamePlayer;
use App\Models\GameReaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlayerController extends Controller
{
    // Banned nickname fragments (case-insensitive)
    const BANNED_WORDS = [
        'nigger','nigga','faggot','retard','cunt','fuck','shit','ass','bitch',
        'cock','dick','pussy','whore','slut','bastard','twat','prick','wanker',
        'nazi','hitler','rape','racist',
    ];

    private function isNicknameBanned(string $nickname): bool
    {
        $lower = strtolower($nickname);
        foreach (self::BANNED_WORDS as $word) {
            if (str_contains($lower, $word)) return true;
        }
        return false;
    }

    public function joinForm()
    {
        return view('play.join');
    }

    public function join(Request $request)
    {
        $request->validate([
            'pin'      => ['required', 'string', 'size:6'],
            'nickname' => ['required', 'string', 'min:2', 'max:20', 'regex:/^[a-zA-Z0-9_\-\s]+$/'],
        ]);

        if ($this->isNicknameBanned($request->nickname)) {
            return back()->withErrors(['nickname' => 'That nickname is not allowed. Please choose another.'])->withInput();
        }

        $game = Game::where('pin', $request->pin)->where('status', 'waiting')->first();

        // Check if game is in progress — offer rejoin
        if (!$game) {
            $activeGame = Game::where('pin', $request->pin)->whereIn('status', ['question','reviewing'])->first();
            if ($activeGame) {
                // Check if player was in this game
                $existingPlayer = $activeGame->players()->where('nickname', $request->nickname)->first();
                if ($existingPlayer) {
                    session(['player_id_' . $activeGame->pin => $existingPlayer->id]);
                    return redirect()->route('play.game', $activeGame->pin);
                }
                return back()->withErrors(['pin' => 'This game is already in progress. Ask the host if you can rejoin.'])->withInput();
            }
            return back()->withErrors(['pin' => 'Game not found or already finished. Check the PIN and try again.'])->withInput();
        }

        $exists = $game->players()->where('nickname', $request->nickname)->exists();
        if ($exists) {
            return back()->withErrors(['nickname' => 'That nickname is already taken in this game.'])->withInput();
        }

        $playerAccountId = session('player_account_id');

        $player = $game->players()->create([
            'nickname'          => $request->nickname,
            'score'             => 0,
            'session_id'        => session()->getId(),
            'last_seen_at'      => now(),
            'power_ups'         => [],
            'player_account_id' => $playerAccountId,
        ]);

        $player->load('game');
        broadcast(new PlayerJoined($player));

        session(['player_id_' . $game->pin => $player->id]);

        return redirect()->route('play.lobby', $game->pin);
    }

    public function lobby(string $pin)
    {
        $game     = Game::where('pin', $pin)->firstOrFail();
        $playerId = session('player_id_' . $pin);

        if (!$playerId) {
            return redirect()->route('play.join')->withErrors(['pin' => 'Session expired. Rejoin the game.']);
        }

        $player = GamePlayer::find($playerId);
        if (!$player) {
            return redirect()->route('play.join')->withErrors(['pin' => 'You were removed from the game.']);
        }

        // If game already started, send to game
        if (in_array($game->status, ['question', 'reviewing'])) {
            return redirect()->route('play.game', $pin);
        }

        return view('play.lobby', compact('game', 'player'));
    }

    public function game(string $pin)
    {
        $game = Game::where('pin', $pin)->with('quiz.questions.answers')->firstOrFail();

        if ($game->status === 'finished') return redirect()->route('play.final', $pin);
        if ($game->status === 'waiting')  return redirect()->route('play.lobby', $pin);

        $playerId = session('player_id_' . $pin);
        if (!$playerId) return redirect()->route('play.join')->withErrors(['pin' => 'Session expired. Please rejoin.']);

        $player = GamePlayer::find($playerId);
        if (!$player) return redirect()->route('play.join')->withErrors(['pin' => 'Could not find your player record. Please rejoin.']);

        return view('play.game', compact('game', 'player'));
    }

    public function submitAnswer(Request $request, string $pin)
    {
        $request->validate([
            'answer_ids'       => ['required', 'array', 'min:1'],
            'answer_ids.*'     => ['integer'],
            'question_id'      => ['required', 'integer'],
            'response_time_ms' => ['required', 'integer', 'min:0'],
            'power_up'         => ['nullable', 'string', 'in:double_points,fifty_fifty,extra_time,spy'],
        ]);

        $game     = Game::where('pin', $pin)->where('status', 'question')->firstOrFail();
        $playerId = session('player_id_' . $pin);
        if (!$playerId) return response()->json(['error' => 'Not in game'], 403);

        $player = GamePlayer::find($playerId);
        if (!$player) return response()->json(['error' => 'Player not found'], 403);
        if ($player->is_spectator) return response()->json(['error' => 'Spectators cannot answer'], 403);

        $alreadyAnswered = GameAnswer::where('game_id', $game->id)
            ->where('game_player_id', $player->id)
            ->where('question_id', $request->question_id)
            ->exists();

        if ($alreadyAnswered) return response()->json(['error' => 'Already answered'], 400);

        $question = $game->quiz->questions()->with('answers')->find($request->question_id);
        if (!$question) return response()->json(['error' => 'Question not found'], 404);

        $answerIds    = $request->answer_ids;
        $submittedIds = array_map('intval', $answerIds);
        $correctIds   = $question->answers->where('is_correct', true)->pluck('id')->map(fn($id) => (int)$id)->toArray();

        if (!$question->multiple_correct && count($answerIds) > 1) {
            return response()->json(['error' => 'Only one answer allowed for this question'], 422);
        }

        // Determine verdict: true | 'partial' | false
        if ($question->multiple_correct) {
            $correctSelected = count(array_intersect($submittedIds, $correctIds));
            $wrongSelected   = count(array_diff($submittedIds, $correctIds));
            $totalCorrect    = count($correctIds);
            $partialFraction = $totalCorrect > 0 ? max(0, $correctSelected - $wrongSelected) / $totalCorrect : 0.0;

            if ($partialFraction >= 1.0) {
                $verdict = true;
            } elseif ($partialFraction > 0) {
                $verdict = 'partial';
            } else {
                $verdict = false;
            }
        } else {
            $verdict         = (bool) ($question->answers->find($submittedIds[0])?->is_correct ?? false);
            $partialFraction = $verdict ? 1.0 : 0.0;
        }

        $pointsEarned = 0;
        $streakBonus  = 0;
        $powerUpUsed  = null;
        $multiplier   = 1;
        $helpPenalty  = 1.0;

        if ($request->power_up && $player->hasPowerUp($request->power_up)) {
            $powerUpUsed = $request->power_up;
            if ($powerUpUsed === 'double_points') $multiplier = 2;
            if ($powerUpUsed === 'fifty_fifty')   $helpPenalty = 0.5;  // 50% points if correct
            if ($powerUpUsed === 'spy')           $helpPenalty = 0.6;  // 60% points if correct (used intel)
            $used   = $player->power_ups ?? [];
            $used[] = $powerUpUsed;
            $player->update(['power_ups' => $used]);
        }

        if ($partialFraction > 0) {
            $timeLimit   = $question->time_limit * 1000;
            $elapsed     = min($request->response_time_ms, $timeLimit);
            $speedFactor = round(1 - (($elapsed / $timeLimit) * 0.5), 4);
            $base        = (int) round($question->points * $partialFraction * $speedFactor * $multiplier * $helpPenalty);
            $newStreak   = $verdict === true ? $player->streak + 1 : 0;
            if ($verdict === true && $newStreak >= 3) {
                $streakBonus = (int) round($base * min(($newStreak - 2) * 0.1, 0.5));
            }
            $pointsEarned = $base + $streakBonus;
            if ($verdict === true) {
                $player->update(['streak' => $newStreak, 'best_streak' => max($player->best_streak, $newStreak)]);
            } else {
                $player->update(['streak' => 0]);
            }
        } else {
            // Double points wrong = lose points. Other power-ups = no penalty on wrong answer.
            if ($powerUpUsed === 'double_points') {
                $timeLimit    = $question->time_limit * 1000;
                $elapsed      = min($request->response_time_ms, $timeLimit);
                $speedFactor  = round(1 - (($elapsed / $timeLimit) * 0.5), 4);
                $pointsEarned = -(int) round($question->points * $speedFactor * $multiplier); // negative!
            }
            $player->update(['streak' => 0]);
        }

        foreach ($answerIds as $aid) {
            GameAnswer::create([
                'game_id'          => $game->id,
                'game_player_id'   => $player->id,
                'question_id'      => $question->id,
                'answer_id'        => $aid,
                'response_time_ms' => $request->response_time_ms,
                'points_earned'    => $pointsEarned,
                'power_up_used'    => $powerUpUsed,
                'streak_bonus'     => $streakBonus,
            ]);
        }

        $player->increment('score', $pointsEarned);
        broadcast(new AnswerCountUpdated($game, $question->id));

        return response()->json([
            'correct'       => $verdict,
            'points_earned' => $pointsEarned,
            'streak_bonus'  => $streakBonus,
            'streak'        => $player->fresh()->streak,
            'total_score'   => $player->fresh()->score,
        ]);
    }

    public function final(string $pin)
    {
        $game     = Game::where('pin', $pin)->with('quiz')->firstOrFail();
        $playerId = session('player_id_' . $pin);

        $player  = $playerId ? GamePlayer::find($playerId) : null;
        $players = $game->players()->where('is_spectator', false)->orderByDesc('score')->get();

        $rank = null;
        if ($player) {
            // search() returns false, not -1, when the player isn't in the ranked list
            // (a spectator, or a player removed by stale-cleanup before viewing results).
            // false + 1 === 1 in PHP, which silently showed such players as rank #1.
            $index = $players->search(fn($p) => $p->id === $player->id);
            $rank  = $index === false ? null : $index + 1;
        }

        $personalStats = null;
        if ($player) {
            $answers = GameAnswer::where('game_id', $game->id)
                ->where('game_player_id', $player->id)
                ->with(['question', 'answer'])
                ->get()
                ->groupBy('question_id');

            $totalQuestions  = $game->quiz->questions()->count();
            $correctCount    = 0;
            $statsByQuestion = [];

            foreach ($game->quiz->questions()->with('answers')->get() as $q) {
                $playerAnswers = $answers->get($q->id, collect());
                $wasCorrect    = false;

                if ($q->multiple_correct) {
                    $correctIds  = $q->answers->where('is_correct', true)->pluck('id')->toArray();
                    $selectedIds = $playerAnswers->pluck('answer_id')->toArray();
                    $wasCorrect  = empty(array_diff($correctIds, $selectedIds)) && empty(array_diff($selectedIds, $correctIds));
                } else {
                    $wasCorrect = $playerAnswers->first()?->answer?->is_correct ?? false;
                }

                if ($wasCorrect) $correctCount++;
                $statsByQuestion[] = [
                    'question' => $q->question_text,
                    'correct'  => $wasCorrect,
                    'points'   => $playerAnswers->sum('points_earned'),
                    'time_ms'  => $playerAnswers->first()?->response_time_ms ?? 0,
                ];
            }

            $personalStats = [
                'correct'     => $correctCount,
                'total'       => $totalQuestions,
                'accuracy'    => $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0,
                'best_streak' => $player->best_streak,
                'questions'   => $statsByQuestion,
            ];
        }

        if ($player && $player->playerAccount) {
            $isWinner = $rank === 1;
            $player->playerAccount->increment('games_played');
            $player->playerAccount->increment('total_score', $player->score);
            if ($isWinner) $player->playerAccount->increment('wins');
        }

        return view('play.final', compact('game', 'player', 'players', 'rank', 'personalStats'));
    }

    public function heartbeat(string $pin)
    {
        $playerId = session('player_id_' . $pin);
        if (!$playerId) return response()->json(['ok' => false]);
        $player = GamePlayer::find($playerId);
        if ($player) {
            $player->update(['last_seen_at' => now()]);
            GamePlayer::removeStale($player->game_id);
        }
        return response()->json(['ok' => true]);
    }

    public function leave(string $pin)
    {
        $playerId = session('player_id_' . $pin);
        if ($playerId) {
            $player = GamePlayer::find($playerId);
            if ($player) {
                $game = Game::where('pin', $pin)->first();
                $player->delete();
                if ($game) broadcast(new PlayerLeft($game));
            }
            session()->forget('player_id_' . $pin);
        }
        return response()->json(['ok' => true]);
    }

    public function react(Request $request, string $pin)
    {
        $request->validate(['emoji' => ['required', 'string', 'max:10']]);
        $playerId = session('player_id_' . $pin);
        if (!$playerId) return response()->json(['error' => 'Not in game'], 403);
        $player = GamePlayer::find($playerId);
        if (!$player) return response()->json(['error' => 'Player not found'], 403);

        $lastReaction = GameReaction::where('game_player_id', $player->id)
            ->where('created_at', '>', now()->subSeconds(2))->exists();
        if ($lastReaction) return response()->json(['error' => 'Too fast'], 429);

        GameReaction::create(['game_id' => $player->game_id, 'game_player_id' => $player->id, 'emoji' => $request->emoji]);
        broadcast(new EmojiReacted($pin, $request->emoji, $player->nickname));
        return response()->json(['ok' => true]);
    }

    public function usePowerUp(Request $request, string $pin)
    {
        $request->validate(['type' => ['required', 'string', 'in:double_points,fifty_fifty,spy'], 'question_id' => ['required','integer']]);
        $playerId = session('player_id_' . $pin);
        if (!$playerId) return response()->json(['error' => 'Not in game'], 403);
        $player = GamePlayer::find($playerId);
        if (!$player || !$player->hasPowerUp($request->type)) return response()->json(['error' => 'Power-up not available'], 400);
        broadcast(new PowerUpUsed($pin, $request->type, $player->nickname, $request->question_id));

        $response = ['ok' => true, 'type' => $request->type];

        if ($request->type === 'fifty_fifty') {
            $question = \App\Models\Question::with('answers')->find($request->question_id);
            if ($question) {
                $wrong = $question->answers->where('is_correct', false)->pluck('id')->shuffle()->take(2)->values();
                $response['eliminate'] = $wrong;
            }
        }

        return response()->json($response);
    }

    public function spy(Request $request, string $pin)
    {
        $playerId = session('player_id_' . $pin);
        if (!$playerId) return response()->json(['error' => 'Not in game'], 403);

        $callingPlayer = GamePlayer::find($playerId);
        if (!$callingPlayer || $callingPlayer->is_spectator) return response()->json(['error' => 'Spectators cannot use power-ups'], 403);

        $game       = Game::where('pin', $pin)->where('status', 'question')->firstOrFail();
        $questionId = $request->query('question_id');

        // Find the current leader (highest score, not the caller, not a spectator)
        $leader = $game->players()
            ->where('is_spectator', false)
            ->where('id', '!=', $playerId)
            ->orderByDesc('score')
            ->first();

        if (!$leader) return response()->json(['correct' => null]);

        // Return whether the leader answered correctly — not which answer they chose
        $leaderAnswer = GameAnswer::where('game_id', $game->id)
            ->where('game_player_id', $leader->id)
            ->where('question_id', $questionId)
            ->first();

        if (!$leaderAnswer) return response()->json(['correct' => null]);

        return response()->json(['correct' => (bool) $leaderAnswer->is_correct]);
    }

    public function kick(string $pin, $playerId)
    {
        $game   = Game::where('pin', $pin)->where('user_id', auth()->id())->firstOrFail();
        $player = GamePlayer::where('id', $playerId)->where('game_id', $game->id)->firstOrFail();
        $player->delete();
        broadcast(new PlayerKicked($game, (int) $playerId));
        broadcast(new PlayerLeft($game));
        return response()->json(['ok' => true, 'count' => $game->players()->count()]);
    }
}
