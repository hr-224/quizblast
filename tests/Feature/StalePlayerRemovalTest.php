<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StalePlayerRemovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeGame(string $status): Game
    {
        $host = User::create(['name' => 'Host', 'email' => 'stale@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        return Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '940001', 'status' => $status, 'current_question' => 0]);
    }

    /** A no-show in the lobby (never played, tab closed before launch) is still cleaned up after 20s. */
    public function test_a_stale_player_is_removed_while_the_game_is_still_waiting(): void
    {
        $game = $this->makeGame('waiting');
        $player = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'NoShow', 'score' => 0, 'streak' => 0, 'best_streak' => 0, 'last_seen_at' => now()->subSeconds(30)]);

        GamePlayer::removeStale($game->id);

        $this->assertNull(GamePlayer::find($player->id));
    }

    /**
     * Once the game has started, a phone that goes idle (screen lock, backgrounded tab
     * during the teacher's explanation) must NOT be removed — that would silently drop
     * the student and their later answers would fail with "Time's up!".
     */
    public function test_a_stale_player_is_kept_once_the_game_has_started(): void
    {
        $game = $this->makeGame('question');
        $player = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Idle', 'score' => 500, 'streak' => 0, 'best_streak' => 0, 'last_seen_at' => now()->subSeconds(30)]);

        GamePlayer::removeStale($game->id);

        $this->assertNotNull(GamePlayer::find($player->id));
        $this->assertSame(500, GamePlayer::find($player->id)->score);
    }

    public function test_a_stale_player_is_kept_while_reviewing_too(): void
    {
        $game = $this->makeGame('reviewing');
        $player = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Idle', 'score' => 0, 'streak' => 0, 'best_streak' => 0, 'last_seen_at' => now()->subSeconds(30)]);

        GamePlayer::removeStale($game->id);

        $this->assertNotNull(GamePlayer::find($player->id));
    }
}
