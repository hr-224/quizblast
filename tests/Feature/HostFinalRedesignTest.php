<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesHostGame;
use Tests\Concerns\MakesPlayerGame;
use Tests\TestCase;

class HostFinalRedesignTest extends TestCase
{
    use RefreshDatabase, MakesHostGame, MakesPlayerGame;

    private function html(int $players = 3): string
    {
        ['host' => $host, 'game' => $game, 'players' => $p] = $this->makeHostGame('finished', 2, $players);
        foreach ($p as $i => $player) {
            $player->update(['score' => 3000 - $i * 1000, 'best_streak' => $i === 0 ? 4 : 0]);
        }
        return $this->actingAs($host)->get(route('game.final', $game))->assertOk()->getContent();
    }

    private function src(): string
    {
        return file_get_contents(resource_path('views/host/final.blade.php'));
    }

    public function test_results_page_renders_podium_leaderboard_and_actions(): void
    {
        $html = $this->html();
        foreach ([
            'id="suspense-overlay"', 'id="main-results" class="host-final hidden"', 'class="podium"', 'podium-1', 'podium-2', 'podium-3',
            'Player1', 'Player2', 'Player3', 'lb-rank-1', 'Game over!', 'Host Quiz', '4 streak', 'Play again', 'Detailed stats', 'Edit quiz',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        preg_match_all('/class="leaderboard-name">\s*(Player\d)/', $html, $m);
        $this->assertSame(['Player1', 'Player2', 'Player3'], $m[1]);
    }

    public function test_page_copes_with_no_players(): void
    {
        $html = $this->html(0);
        $this->assertStringContainsString('id="main-results"', $html);
        $this->assertStringNotContainsString('class="podium"', $html);
    }

    public function test_final_view_has_no_inline_styles_or_handlers(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style|\son(click|submit)\s*=/i', $this->src());
    }

    public function test_confetti_only_fires_in_party_mode(): void
    {
        $src = $this->src();
        $this->assertStringContainsString("QB.UI.fx() === 'party'", $src);
        $this->assertStringNotContainsString('QB.Confetti.burst(', str_replace('QB.Confetti.burst(n)', '', $src));
    }

    public function test_script_avoids_optional_chaining_and_uses_text_content(): void
    {
        preg_match('/<script>\s*document\.addEventListener.*?<\/script>/s', $this->src(), $m);
        $js = $m[0] ?? '';
        $this->assertNotSame('', $js);
        $this->assertStringNotContainsString('?.', $js);
        $this->assertStringNotContainsString('innerHTML', $js);
        $this->assertStringContainsString("getElementById('suspense-name').textContent", $js);
    }

    public function test_assets_are_cache_busted(): void
    {
        $src = $this->src();
        $this->assertStringContainsString("sounds.js?v={{ filemtime(", $src);
        $this->assertStringContainsString("confetti.js?v={{ filemtime(", $src);
    }

    public function test_every_id_the_script_reads_exists_in_the_markup(): void
    {
        $this->assertScriptIdsExist('host/final.blade.php');
    }

    public function test_final_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach (['.host-final{', '.host-final .podium-1 .podium-block', '.podium-streak', '.host-final-actions', '.suspense-overlay.is-host'] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
