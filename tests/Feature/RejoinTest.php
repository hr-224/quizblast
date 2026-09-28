<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\GameAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RejoinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    /** Three-question game, currently on question index 2. */
    private function makeGame(string $status = 'question'): array
    {
        $host = User::create(['name' => 'Host', 'email' => 'host@test.com', 'password' => bcrypt('pw')]);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);

        $questions = [];
        foreach ([0, 1, 2] as $i) {
            $q = Question::create([
                'quiz_id' => $quiz->id, 'question_text' => "Q$i",
                'time_limit' => 20, 'points' => 1000, 'order' => $i,
            ]);
            Answer::create(['question_id' => $q->id, 'answer_text' => 'A', 'is_correct' => true,  'order' => 0]);
            Answer::create(['question_id' => $q->id, 'answer_text' => 'B', 'is_correct' => false, 'order' => 1]);
            $questions[] = $q;
        }

        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id,
            'pin' => '777001', 'status' => $status, 'current_question' => 2,
            'question_started_at' => now(),
        ]);
        $player = GamePlayer::create([
            'game_id' => $game->id, 'nickname' => 'Alice', 'score' => 500,
            'streak' => 1, 'best_streak' => 1, 'rejoin_token' => 'tok-original',
            'last_seen_at' => now()->subMinutes(5),
        ]);

        return compact('host', 'quiz', 'questions', 'game', 'player');
    }

    private function rejoin(array $extra = [])
    {
        return $this->post('/play/join', array_merge(['pin' => '777001', 'nickname' => 'Alice'], $extra));
    }

    /** @test */
    public function new_players_get_a_rejoin_token_on_join(): void
    {
        $data = $this->makeGame('waiting');

        $this->post('/play/join', ['pin' => '777001', 'nickname' => 'Bob'])->assertRedirect();

        $bob = GamePlayer::where('nickname', 'Bob')->first();
        $this->assertNotNull($bob->rejoin_token);
        $this->assertSame(40, strlen($bob->rejoin_token));
    }

    /** @test */
    public function rejoin_token_is_never_serialized(): void
    {
        $data = $this->makeGame();

        $this->assertArrayNotHasKey('rejoin_token', $data['player']->toArray());
    }

    /** @test */
    public function matching_token_rejoins_mid_game_and_keeps_score(): void
    {
        ['player' => $player] = $this->makeGame();

        $this->rejoin(['rejoin_token' => 'tok-original'])
            ->assertRedirect(route('play.game', '777001'));

        $this->assertSame($player->id, session('player_id_777001'));
        $this->assertSame(500, $player->fresh()->score);
    }

    /** @test */
    public function rejoin_works_during_reviewing_too(): void
    {
        $this->makeGame('reviewing');

        $this->rejoin(['rejoin_token' => 'tok-original'])
            ->assertRedirect(route('play.game', '777001'));
    }

    /** @test */
    public function bare_nickname_without_token_is_rejected(): void
    {
        $this->makeGame();

        $this->rejoin()
            ->assertSessionHasErrors('nickname');

        $this->assertNull(session('player_id_777001'));
    }

    /** @test */
    public function wrong_token_is_rejected(): void
    {
        $this->makeGame();

        $this->rejoin(['rejoin_token' => 'tok-guess'])
            ->assertSessionHasErrors('nickname');

        $this->assertNull(session('player_id_777001'));
    }

    /** @test */
    public function player_row_with_no_token_cannot_be_claimed_by_empty_token(): void
    {
        ['player' => $player] = $this->makeGame();
        $player->update(['rejoin_token' => null]);

        $this->rejoin(['rejoin_token' => ''])
            ->assertSessionHasErrors('nickname');
    }

    /** @test */
    public function token_rotates_on_rejoin_and_old_token_stops_working(): void
    {
        ['player' => $player] = $this->makeGame();

        $this->rejoin(['rejoin_token' => 'tok-original'])->assertRedirect();

        $new = $player->fresh()->rejoin_token;
        $this->assertNotSame('tok-original', $new);
        $this->assertSame(40, strlen($new));

        // A second browser replaying the old token is refused.
        $this->flushSession();
        $this->rejoin(['rejoin_token' => 'tok-original'])->assertSessionHasErrors('nickname');
    }

    /** @test */
    public function logged_in_owner_can_rejoin_without_a_token(): void
    {
        ['player' => $player] = $this->makeGame();
        $user = User::create(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => bcrypt('pw')]);
        $player->update(['user_id' => $user->id]);

        $this->actingAs($user)->rejoin()
            ->assertRedirect(route('play.game', '777001'));

        $this->assertSame($player->id, session('player_id_777001'));
    }

    /** @test */
    public function a_different_logged_in_user_cannot_take_over_a_linked_player(): void
    {
        ['player' => $player] = $this->makeGame();
        $owner = User::create(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => bcrypt('pw')]);
        $other = User::create(['name' => 'Eve', 'email' => 'eve@test.com', 'password' => bcrypt('pw')]);
        $player->update(['user_id' => $owner->id]);

        $this->actingAs($other)->rejoin()->assertSessionHasErrors('nickname');
    }

    /** @test */
    public function rejoin_links_a_logged_in_user_to_an_anonymous_player(): void
    {
        ['player' => $player] = $this->makeGame();
        $user = User::create(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => bcrypt('pw')]);

        $this->actingAs($user)->rejoin(['rejoin_token' => 'tok-original'])->assertRedirect();

        $this->assertSame($user->id, $player->fresh()->user_id);
    }

    /** @test */
    public function rejoin_refreshes_presence_and_session_id(): void
    {
        ['player' => $player] = $this->makeGame();

        $this->rejoin(['rejoin_token' => 'tok-original'])->assertRedirect();

        $fresh = $player->fresh();
        $this->assertTrue($fresh->last_seen_at->gt(now()->subMinute()));
        $this->assertNotNull($fresh->session_id);
    }

    /** @test */
    public function rejoin_reports_how_many_earlier_questions_were_missed(): void
    {
        ['player' => $player, 'questions' => $qs, 'game' => $game] = $this->makeGame();
        // Alice answered question 0 but missed question 1; question 2 is live.
        GameAnswer::create([
            'game_id' => $game->id, 'game_player_id' => $player->id,
            'question_id' => $qs[0]->id, 'answer_id' => $qs[0]->answers()->first()->id,
            'response_time_ms' => 1000, 'points_earned' => 500,
        ]);

        $this->rejoin(['rejoin_token' => 'tok-original'])
            ->assertSessionHas('rejoined', 1);
    }

    /** @test */
    public function missed_count_is_zero_when_nothing_was_missed(): void
    {
        ['player' => $player, 'questions' => $qs, 'game' => $game] = $this->makeGame();
        foreach ([0, 1] as $i) {
            GameAnswer::create([
                'game_id' => $game->id, 'game_player_id' => $player->id,
                'question_id' => $qs[$i]->id, 'answer_id' => $qs[$i]->answers()->first()->id,
                'response_time_ms' => 1000, 'points_earned' => 500,
            ]);
        }

        $this->rejoin(['rejoin_token' => 'tok-original'])->assertSessionHas('rejoined', 0);
    }

    /** @test */
    public function unknown_nickname_mid_game_gets_the_in_progress_message(): void
    {
        $this->makeGame();

        $this->post('/play/join', ['pin' => '777001', 'nickname' => 'Newcomer'])
            ->assertSessionHasErrors('pin');
        $this->assertSame(0, GamePlayer::where('nickname', 'Newcomer')->count());
    }

    /** @test */
    public function finished_games_cannot_be_rejoined(): void
    {
        $this->makeGame('finished');

        $this->rejoin(['rejoin_token' => 'tok-original'])->assertSessionHasErrors('pin');
    }

    /** @test */
    public function game_page_stores_the_players_token_for_later_rejoin(): void
    {
        ['player' => $player] = $this->makeGame();

        $this->withSession(['player_id_777001' => $player->id])
            ->get('/play/777001/game')
            ->assertOk()
            ->assertSee('qb_rejoin_', false)
            ->assertSee('tok-original', false);
    }

    /** @test */
    public function join_form_carries_a_hidden_token_field(): void
    {
        $this->get('/play')
            ->assertOk()
            ->assertSee('name="rejoin_token"', false);
    }
}
