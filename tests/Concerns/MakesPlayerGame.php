<?php

namespace Tests\Concerns;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Quiz;
use App\Models\User;

trait MakesPlayerGame
{
    /** @return array{game: Game, player: GamePlayer, quiz: Quiz} */
    protected function makePlayerGame(string $status, string $pin = '777001', array $playerAttrs = []): array
    {
        $host = User::create(['name' => 'Host', 'email' => "host{$pin}@test.com", 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => $pin,
            'status' => $status, 'current_question' => 0,
        ]);
        $player = GamePlayer::create(array_merge([
            'game_id' => $game->id, 'nickname' => 'P1', 'score' => 0, 'streak' => 0, 'best_streak' => 0,
        ], $playerAttrs));

        return compact('game', 'player', 'quiz');
    }

    /** Every getElementById('literal') in a view's JS must exist as id="literal" in that same view. */
    protected function assertScriptIdsExist(string $bladeRelativePath): void
    {
        $src = file_get_contents(resource_path('views/' . $bladeRelativePath));
        preg_match_all("/getElementById\('([^']+)'\)/", $src, $m);
        $this->assertNotEmpty($m[1], 'no getElementById calls found — regex out of date?');
        foreach (array_unique($m[1]) as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $src, "script uses #{$id} but the markup has no such id");
        }
    }
}
