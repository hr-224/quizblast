<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerFinalRankTest extends TestCase
{
    use RefreshDatabase;

    /** A spectator (excluded from the ranked list) must not be shown as rank 1 with confetti/gold. */
    public function test_a_spectator_viewing_final_results_is_not_shown_as_rank_one(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'rank@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Rank Quiz']);
        $game = Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '920001', 'status' => 'finished', 'current_question' => 0]);
        GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Real1', 'score' => 900, 'streak' => 0, 'best_streak' => 0]);
        $spectator = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Watcher', 'score' => 0, 'streak' => 0, 'best_streak' => 0, 'is_spectator' => true]);
        session(['player_id_920001' => $spectator->id]);

        $html = $this->get('/play/920001/final')->assertOk()->getContent();

        // A rank-1 view shows the gold medal and "#1 of N players" — neither may appear for the spectator.
        $this->assertStringNotContainsString('#1 of', $html);
    }
}
