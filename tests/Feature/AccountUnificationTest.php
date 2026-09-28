<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountUnificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_stat_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['total_score', 'games_played', 'wins']));
    }

    public function test_game_players_points_at_users_not_player_accounts(): void
    {
        $this->assertTrue(Schema::hasColumn('game_players', 'user_id'));
        $this->assertFalse(Schema::hasColumn('game_players', 'player_account_id'));
    }

    public function test_player_accounts_table_is_gone(): void
    {
        $this->assertFalse(Schema::hasTable('player_accounts'));
    }

    public function test_user_has_stat_helpers_and_game_players_relation(): void
    {
        $user = \App\Models\User::create([
            'name' => 'Stat Fan', 'email' => 'unify-model@test.com', 'password' => 'secret-pass',
            'total_score' => 4500, 'games_played' => 3, 'wins' => 1,
        ]);
        $this->assertSame('33%', $user->win_rate);

        $quiz = \App\Models\Quiz::create(['user_id' => $user->id, 'title' => 'Played Quiz']);
        $game = \App\Models\Game::create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'pin' => '960001', 'status' => 'finished', 'current_question' => 0]);
        \App\Models\GamePlayer::create(['game_id' => $game->id, 'nickname' => 'StatFan1', 'score' => 800, 'streak' => 0, 'best_streak' => 4, 'user_id' => $user->id]);

        $this->assertCount(1, $user->gamePlayers);
        $this->assertCount(1, $user->getRecentGames());
    }

    public function test_user_win_rate_handles_zero_games_without_dividing_by_zero(): void
    {
        $user = \App\Models\User::create(['name' => 'New Host', 'email' => 'unify-zero@test.com', 'password' => 'secret-pass']);
        $this->assertSame('0%', $user->win_rate);
    }

    public function test_game_player_user_relation_resolves(): void
    {
        $user = \App\Models\User::create(['name' => 'Rel Test', 'email' => 'unify-rel@test.com', 'password' => 'secret-pass']);
        $quiz = \App\Models\Quiz::create(['user_id' => $user->id, 'title' => 'Q']);
        $game = \App\Models\Game::create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'pin' => '960002', 'status' => 'waiting', 'current_question' => 0]);
        $gp = \App\Models\GamePlayer::create(['game_id' => $game->id, 'nickname' => 'P', 'score' => 0, 'streak' => 0, 'best_streak' => 0, 'user_id' => $user->id]);

        $this->assertTrue($gp->user->is($user));
    }

    public function test_registering_always_creates_a_user_and_logs_them_in(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $response = $this->post(route('register'), [
            'name' => 'New Person', 'email' => 'unify-register@test.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'unify-register@test.com']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_register_page_has_no_account_type_toggle(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();
        $this->assertStringNotContainsString('account_type', $html);
        $this->assertStringNotContainsString('account-type-toggle', $html);
        $this->assertStringNotContainsString('data-type="player"', $html);
        $this->assertStringNotContainsString('data-type="host"', $html);
    }

    public function test_login_is_a_single_path_with_no_player_account_fallback(): void
    {
        $src = file_get_contents(app_path('Http/Controllers/Auth/LoginController.php'));
        $this->assertStringNotContainsString('PlayerAccount', $src);
    }

    public function test_login_page_copy_no_longer_mentions_two_account_types(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();
        $this->assertStringNotContainsString('host or player account', $html);
    }

    public function test_logged_in_user_joining_a_game_links_their_account(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = \App\Models\User::create(['name' => 'Joiner', 'email' => 'unify-join@test.com', 'password' => 'secret-pass']);
        $quiz = \App\Models\Quiz::create(['user_id' => $user->id, 'title' => 'Q']);
        $game = \App\Models\Game::create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'pin' => '960003', 'status' => 'waiting', 'current_question' => 0]);

        $this->actingAs($user)->post(route('play.join.post'), ['pin' => '960003', 'nickname' => 'JoinerNick']);

        $this->assertDatabaseHas('game_players', ['game_id' => $game->id, 'nickname' => 'JoinerNick', 'user_id' => $user->id]);
    }

    public function test_a_host_can_join_and_play_their_own_hosted_game(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $host = \App\Models\User::create(['name' => 'Self Host', 'email' => 'unify-selfplay@test.com', 'password' => 'secret-pass']);
        $quiz = \App\Models\Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        $game = \App\Models\Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '960004', 'status' => 'waiting', 'current_question' => 0]);

        $response = $this->actingAs($host)->post(route('play.join.post'), ['pin' => '960004', 'nickname' => 'HostAsPlayer']);

        $response->assertRedirect(route('play.lobby', '960004'));
        $this->assertDatabaseHas('game_players', ['game_id' => $game->id, 'nickname' => 'HostAsPlayer', 'user_id' => $host->id]);
    }

    public function test_anonymous_join_still_leaves_user_id_null(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $host = \App\Models\User::create(['name' => 'Host', 'email' => 'unify-anon@test.com', 'password' => 'secret-pass']);
        $quiz = \App\Models\Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        $game = \App\Models\Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '960005', 'status' => 'waiting', 'current_question' => 0]);

        $this->post(route('play.join.post'), ['pin' => '960005', 'nickname' => 'Guest']);

        $this->assertDatabaseHas('game_players', ['game_id' => $game->id, 'nickname' => 'Guest', 'user_id' => null]);
    }
}
