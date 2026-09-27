<?php

namespace Tests\Feature;

use App\Models\GameAnswer;
use App\Models\GamePlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesHostGame;
use Tests\Concerns\MakesPlayerGame;
use Tests\TestCase;

class HostStandingsTest extends TestCase
{
    use RefreshDatabase, MakesHostGame, MakesPlayerGame;

    /** P1 500, P2 900 (earned 900 this question), P3 300 → before: P1,P3,P2 — after: P2,P1,P3 */
    private function reviewing(int $questions = 2, array $extraPlayers = [], bool $doubleRow = false, string $pin = '555001'): string
    {
        ['host' => $host, 'game' => $game, 'questions' => $qs, 'players' => $p] = $this->makeHostGame('reviewing', $questions, 3, $pin);
        [$p1, $p2, $p3] = $p;
        $p1->update(['score' => 500]);
        $p2->update(['score' => 900]);
        $p3->update(['score' => 300]);
        $answerId = $qs[0]->answers[1]->id;
        GameAnswer::create(['game_id' => $game->id, 'game_player_id' => $p2->id, 'question_id' => $qs[0]->id, 'answer_id' => $answerId, 'points_earned' => 900]);
        if ($doubleRow) {   // multi-correct: a second row for the same player carries the same points
            GameAnswer::create(['game_id' => $game->id, 'game_player_id' => $p2->id, 'question_id' => $qs[0]->id, 'answer_id' => $qs[0]->answers[2]->id, 'points_earned' => 900]);
        }
        foreach ($extraPlayers as $i => $score) {
            GamePlayer::create(['game_id' => $game->id, 'nickname' => "Extra{$i}", 'score' => $score, 'streak' => 0, 'best_streak' => 0, 'session_id' => "x{$i}"]);
        }

        return $this->actingAs($host)->get(route('game.question', $game))->assertOk()->getContent();
    }

    private function src(): string
    {
        return file_get_contents(resource_path('views/host/question.blade.php'));
    }

    public function test_rows_are_ordered_by_score_and_carry_the_places_moved(): void
    {
        $html = $this->reviewing();
        $this->assertSame(1, substr_count($html, 'data-move="2"'));    // Player2 jumped from 3rd to 1st
        $this->assertSame(2, substr_count($html, 'data-move="-1"'));   // Player1 and Player3 each lost one place
        preg_match_all('/class="standings-name">\s*(Player\d)/', $html, $m);
        $this->assertSame(['Player2', 'Player1', 'Player3'], $m[1]);
    }

    public function test_points_gained_use_max_not_sum_for_multi_correct_answers(): void
    {
        $html = $this->reviewing(2, [], true);
        $this->assertStringContainsString('+900', $html);
        $this->assertStringNotContainsString('+1,800', $html);
        $this->assertSame(2, substr_count($html, 'data-move="-1"'));
    }

    public function test_only_the_top_five_are_listed(): void
    {
        $html = $this->reviewing(2, [10, 20, 30, 40]);   // 7 players in total
        $this->assertSame(5, substr_count($html, 'class="standings-row"'));
    }

    public function test_popup_only_exists_when_reviewing_a_non_last_question(): void
    {
        $this->assertStringContainsString('id="standings"', $this->reviewing(2));
        $this->assertStringNotContainsString('id="standings"', $this->reviewing(1, [], false, '555002'));   // last question → auto-advance instead
        ['host' => $host, 'game' => $game] = $this->makeHostGame('question', 2, 2, '555003');
        $this->assertStringNotContainsString('id="standings"', $this->actingAs($host)->get(route('game.question', $game))->getContent());
    }

    public function test_next_button_opens_the_popup_and_the_popup_advances(): void
    {
        $html = $this->reviewing();
        $this->assertMatchesRegularExpression('/<button type="button"[^>]*id="next-btn"/', $html);
        $this->assertStringContainsString('id="standings-next"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertMatchesRegularExpression(
            '#<form method="POST" action="[^"]*/host/\d+/next">\s*<input[^>]*name="_token"[^>]*>\s*<button type="submit" class="btn btn-success btn-xl" id="standings-next"#',
            $html
        );
    }

    /**
     * Final-review Group C: two players tied on score, both before and after this question,
     * must render in a deterministic order (ascending player id) on every render.
     */
    public function test_tied_scores_render_in_deterministic_ascending_id_order(): void
    {
        ['host' => $host, 'game' => $game, 'players' => $p] = $this->makeHostGame('reviewing', 2, 2, '555009');
        [$p1, $p2] = $p;
        $p1->update(['score' => 500]);
        $p2->update(['score' => 500]);

        $names = function () use ($host, $game) {
            $html = $this->actingAs($host)->get(route('game.question', $game))->assertOk()->getContent();
            preg_match_all('/class="standings-name">\s*(Player\d)/', $html, $m);
            return $m[1];
        };

        $first  = $names();
        $second = $names();

        $this->assertSame([$p1->nickname, $p2->nickname], $first, 'tied scores should render in ascending player-id order');
        $this->assertSame($first, $second, 'the order must be identical across renders, not just stable-by-luck');
    }

    public function test_popup_script_sets_move_from_data_and_closes_on_escape(): void
    {
        $src = $this->src();
        $this->assertStringContainsString("row.dataset.move", $src);
        $this->assertStringContainsString("setProperty('--move'", $src);
        $this->assertStringContainsString("e.key === 'Escape'", $src);
        $this->assertStringNotContainsString('?.', $src);
    }

    public function test_the_view_still_has_no_inline_styles_beyond_the_chart_bar(): void
    {
        preg_match_all('/\sstyle\s*=\s*"([^"]*)"/i', $this->src(), $m);
        $this->assertCount(1, $m[1]);
    }

    public function test_standings_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach ([
            '.standings{', '.standings-panel', '.standings-row', '.standings-rank', '.standings-name', '.standings-gain',
            '.standings-move', '.is-up', '.is-down', '.is-same', '.standings-score', '@keyframes standings-move', '--move',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
