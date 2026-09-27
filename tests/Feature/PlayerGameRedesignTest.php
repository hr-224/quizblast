<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesPlayerGame;
use Tests\TestCase;

class PlayerGameRedesignTest extends TestCase
{
    use RefreshDatabase, MakesPlayerGame;

    private function html(array $playerAttrs = []): string
    {
        ['game' => $game, 'player' => $player] = $this->makePlayerGame('question', '777001', $playerAttrs);
        return $this->withSession(['player_id_' . $game->pin => $player->id])
            ->get(route('play.game', $game->pin))->assertOk()->getContent();
    }

    private function src(): string
    {
        return file_get_contents(resource_path('views/play/game.blade.php'));
    }

    public function test_page_has_every_state_container_and_hook(): void
    {
        $html = $this->html();
        foreach ([
            'id="state-waiting"', 'id="state-question"', 'id="state-reviewing"', 'id="state-finished"',
            'id="reading-overlay"', 'id="player-ring"', 'id="player-reading-num"',
            'id="player-timer-wrap"', 'id="player-timer-bar"', 'id="q-progress"', 'id="multi-hint"',
            'id="answer-grid"', 'id="answered-msg"', 'id="answered-block"', 'id="answered-score-display"',
            'id="player-tray"', 'id="submit-multi-btn"', 'id="power-ups-bar"',
            'id="pu-double"', 'id="pu-fifty"', 'id="pu-spy"',
            'id="review-verdict-card"', 'id="review-icon"', 'id="review-title"', 'id="review-points"',
            'id="review-streak"', 'id="review-rank"', 'id="review-answers"', 'id="review-score"',
            'id="my-score"', 'id="streak-chip"', 'id="streak-num"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_every_id_the_script_reads_exists_in_the_markup(): void
    {
        $this->assertScriptIdsExist('play/game.blade.php');
    }

    public function test_game_view_has_no_inline_styles(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $this->src());
    }

    public function test_answer_tiles_use_shared_shapes_not_glyphs(): void
    {
        $src = $this->src();
        $this->assertStringContainsString('QB.Shapes.svg(', $src);
        $this->assertStringContainsString('/js/shapes.js', $src);
        foreach (['▲', '◆', '●', '■'] as $glyph) {
            $this->assertStringNotContainsString($glyph, $src, "old glyph shape {$glyph} still present");
        }
    }

    /** Review focus: quiz text with HTML must be shown literally. */
    public function test_answer_text_is_never_injected_as_html(): void
    {
        $src = $this->src();
        $this->assertStringNotContainsString('${ans.text}', $src);
        $this->assertDoesNotMatchRegularExpression('/innerHTML\s*=\s*[^;]*ans\.text/', $src);
        $this->assertStringContainsString('label.textContent = ans.text', $src);
    }

    /** Review focus: reloading after using a power-up must still yield valid JS. */
    public function test_used_power_ups_are_serialized_as_valid_json(): void
    {
        $html = $this->html(['power_ups' => ['double_points']]);
        $this->assertStringContainsString('new Set(["double_points"])', $html);
        $this->assertStringNotContainsString('&quot;', $html);
    }

    /** Review focus: reveal/skip during the reading countdown must cancel it. */
    public function test_reading_countdown_is_cancelled_on_state_changes(): void
    {
        $src = $this->src();
        $this->assertGreaterThanOrEqual(3, substr_count($src, 'clearInterval(readInterval)'));
    }

    /** Review focus: multi-select must not be submittable while empty. */
    public function test_multi_submit_starts_disabled_and_tracks_selection(): void
    {
        $src = $this->src();
        $this->assertStringContainsString('submit.disabled = true', $src);
        $this->assertStringContainsString('.disabled = selectedAnswers.size === 0', $src);
    }

    public function test_review_verdict_texts_and_classes_are_kept(): void
    {
        $html = $this->html();
        foreach (['Partial Credit!', 'review-verdict-partial', 'review-verdict-correct', 'review-verdict-wrong', "Time's up!"] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_server_side_fifty_fifty_elimination_is_preserved(): void
    {
        $src = $this->src();
        $this->assertStringContainsString('data.eliminate', $src);
        $this->assertStringContainsString("classList.add('is-eliminated')", $src);
    }

    public function test_game_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach ([
            'body.game-active', '.q-screen', '.q-timer-bar', '.is-warn', '.is-urgent', '.answer-grid', '.player-tray',
            '.reading-overlay', '.ring-fill', '.q-locked', '.answered-block-preview', '.answered-score-badge',
            '.pu-row', '.pu-card', '.pu-double', '.pu-fifty', '.pu-spy', '.is-used',
            '.review-screen', '.review-verdict-card', '.review-verdict-correct', '.review-verdict-wrong',
            '.review-verdict-partial', '.review-answers', '.toast', '.wait-dots',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }

    public function test_review_check_mark_sits_in_the_tile_flow_so_it_cannot_cover_the_label(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertMatchesRegularExpression(
            '/\.review-answers \.ans-tile-mark\{[^}]*position:static/',
            $css
        );
    }

    public function test_review_score_count_up_does_not_replay_on_repeated_renders(): void
    {
        $src = $this->src();
        $this->assertMatchesRegularExpression(
            '/countUp\(document\.getElementById\(\'review-score\'\), scoreBefore, myScore\);\s*\/\/[^\n]*\n\s*scoreBefore = myScore;/',
            $src
        );
    }

    public function test_fifty_fifty_elimination_disables_and_deselects_tiles(): void
    {
        $src = $this->src();
        $this->assertMatchesRegularExpression('/is-eliminated\'\);\s*tile\.disabled = true;/', $src);
        $this->assertStringContainsString('selectedAnswers.delete(Number(tile.dataset.answerId))', $src);
        $this->assertStringContainsString("getElementById('submit-multi-btn').disabled = selectedAnswers.size === 0", $src);
    }

    public function test_multi_hint_uses_spec_wording(): void
    {
        $html = $this->html();
        $this->assertStringContainsString('Select all that apply', $html);
        $this->assertStringNotContainsString('Select all correct answers', $html);
    }

    public function test_live_region_is_on_the_verdict_card_not_the_reading_overlay(): void
    {
        $html = $this->html();
        $this->assertMatchesRegularExpression('/<div id="reading-overlay"(?![^>]*(aria-live|role=))[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<div id="review-verdict-card"[^>]*role="status"[^>]*aria-live="polite"/', $html);
    }

    public function test_a_touchstart_listener_enables_ios_active_states(): void
    {
        $this->assertStringContainsString("document.addEventListener('touchstart', function () {}, { passive: true });", $this->src());
    }

    public function test_review_wrong_tiles_stay_readable_and_wrap_text_on_old_ios(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('.review-answers .ans-tile.is-wrong{opacity:.55}', $css);
        $this->assertStringContainsString('.review-answers .ans-tile-label{flex:1;overflow-wrap:anywhere;word-break:break-word}', $css);
    }
}
