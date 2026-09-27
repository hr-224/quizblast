<?php

namespace Tests\Feature;

use App\Events\PlayerJoined;
use App\Events\PlayerKicked;
use App\Events\PlayerLeft;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerJoinedEventTest extends TestCase
{
    use RefreshDatabase;

    private function makeGameWithSpectator(): array
    {
        $host      = User::create(['name' => 'Host', 'email' => 'evt@test.com', 'password' => 'secret-pass']);
        $quiz      = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        $game      = Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '930001', 'status' => 'waiting', 'current_question' => 0]);
        GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Watcher', 'score' => 0, 'streak' => 0, 'best_streak' => 0, 'is_spectator' => true]);
        $player = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Real', 'score' => 0, 'streak' => 0, 'best_streak' => 0]);

        return [$game, $player];
    }

    /** The live count broadcast to the lobby must match the polling endpoint's count: players only. */
    public function test_player_joined_count_excludes_spectators(): void
    {
        [$game, $player] = $this->makeGameWithSpectator();

        $event = new PlayerJoined($player);
        $data  = $event->broadcastWith();

        $this->assertSame(1, $data['count'], 'one real player, one spectator — count must be 1, not 2');
    }

    public function test_player_left_count_and_list_exclude_spectators(): void
    {
        [$game] = $this->makeGameWithSpectator();

        $event = new PlayerLeft($game);
        $data  = $event->broadcastWith();

        $this->assertSame(1, $data['count']);
        $this->assertCount(1, $data['players']);
        $this->assertSame('Real', $data['players'][0]['nickname']);
    }

    public function test_player_kicked_count_and_list_exclude_spectators(): void
    {
        [$game, $player] = $this->makeGameWithSpectator();
        $kickedId = $player->id;
        $player->delete();   // PlayerController::kick deletes the row before broadcasting

        $event = new PlayerKicked($game, $kickedId);
        $data  = $event->broadcastWith();

        $this->assertSame(0, $data['count'], 'the kicked player was the only real player');
        $this->assertCount(0, $data['players']);
    }
}
