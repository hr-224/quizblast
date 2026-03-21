<?php

namespace App\Http\Controllers;

use App\Events\PlayerJoined;
use App\Models\Game;
use App\Models\GamePlayer;
use Illuminate\Http\Request;

class SpectatorController extends Controller
{
    public function join(string $pin)
    {
        $game = Game::where('pin', $pin)->whereIn('status', ['waiting','question','reviewing'])->first();

        if (!$game || !$game->spectator_mode) {
            return redirect()->route('play.join')->withErrors(['pin' => 'Spectator mode is not available for this game.']);
        }

        $nickname = 'Spectator' . rand(100, 999);
        $player   = $game->players()->create([
            'nickname'     => $nickname,
            'score'        => 0,
            'is_spectator' => true,
            'session_id'   => session()->getId(),
            'last_seen_at' => now(),
            'power_ups'    => [],
        ]);

        session(['spectator_id_' . $pin => $player->id]);

        return redirect()->route('spectate.watch', $pin);
    }

    public function watch(string $pin)
    {
        $game        = Game::where('pin', $pin)->with('quiz.questions.answers')->firstOrFail();
        $spectatorId = session('spectator_id_' . $pin);

        if (!$spectatorId) return redirect()->route('play.join');

        return view('spectator.watch', compact('game'));
    }
}
