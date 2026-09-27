<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesPlayerGame;
use Tests\TestCase;

class PlayerLobbyRedesignTest extends TestCase
{
    use RefreshDatabase, MakesPlayerGame;

    private function html(): string
    {
        ['game' => $game, 'player' => $player] = $this->makePlayerGame('waiting');
        return $this->withSession(['player_id_' . $game->pin => $player->id])
            ->get(route('play.lobby', $game->pin))->assertOk()->getContent();
    }

    public function test_lobby_renders_its_hooks_and_the_players_nickname(): void
    {
        $html = $this->html();
        foreach ([
            'id="leave-btn"', 'id="lobby-count"', 'id="join-ticker"', 'id="conn-dot"', 'id="status-text"',
            'class="lobby-count-pill"', 'class="lobby-nick-badge"', 'P1',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_leave_button_has_no_inline_handler(): void
    {
        $this->assertStringNotContainsString('onclick=', $this->html());
    }

    public function test_lobby_view_has_no_inline_styles(): void
    {
        $src = file_get_contents(resource_path('views/play/lobby.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src);
    }

    public function test_every_id_the_script_reads_exists_in_the_markup(): void
    {
        $this->assertScriptIdsExist('play/lobby.blade.php');
    }

    /** Review focus: js.pusher.com blocked must not stop heartbeats or polling. */
    public function test_lobby_script_works_without_pusher(): void
    {
        $src = file_get_contents(resource_path('views/play/lobby.blade.php'));
        $this->assertStringContainsString('QB.loadPusher', $src);
        $this->assertMatchesRegularExpression('/try\s*\{[^}]*new Pusher/s', $src);
        $this->assertStringContainsString('/api/game/', $src);
        $this->assertStringContainsString("getElementById('leave-btn').addEventListener('click'", $src);
        // the heartbeat must be registered after (outside) the Pusher try/catch, so it runs even when Pusher is missing
        $this->assertGreaterThan(strpos($src, '} catch (e) {'), strpos($src, '/heartbeat'));
    }

    public function test_lobby_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach ([
            '.stat-chip', '.topbar-nick', '.lobby-wrap', '.lobby-count-pill', '.lobby-join-ticker', '.is-visible',
            '.lobby-nick-badge', '.lobby-nick-name', '.lobby-status', '.conn-dot', '.is-live',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }

    public function test_lobby_has_a_name_chip_container(): void
    {
        $this->assertStringContainsString('id="player-chips"', $this->html());
    }

    public function test_chips_are_built_with_text_content_never_html(): void
    {
        $src = file_get_contents(resource_path('views/play/lobby.blade.php'));
        $this->assertStringContainsString('function renderChips', $src);
        preg_match('/function renderChips.*?\n  }\n/s', $src, $m);
        $this->assertNotEmpty($m, 'renderChips body not found');
        $this->assertStringContainsString('textContent', $m[0]);
        $this->assertStringNotContainsString('innerHTML', $m[0]);
        $this->assertStringNotContainsString('innerHTML', $src);
        $this->assertStringContainsString('@json($player->nickname)', $src);
    }

    public function test_lobby_polls_the_players_endpoint_every_five_seconds(): void
    {
        $src = file_get_contents(resource_path('views/play/lobby.blade.php'));
        $this->assertStringContainsString("/api/game/' + pin + '/players'", $src);
        $this->assertStringNotContainsString("/state'", $src);
        $this->assertStringContainsString('}, 5000);', $src);
        $this->assertStringNotContainsString('}, 3000);', str_replace('setTimeout(hideTicker, 3000)', '', $src));
        $this->assertStringContainsString('renderChips(data.players)', $src);
        $this->assertStringContainsString('renderChips(d.players)', $src);
    }

    public function test_lobby_chip_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach (['.lobby-chips', '.lobby-chip{', '.lobby-chip.is-me', '@keyframes chip-drift', 'html[data-fx="party"] .lobby-chip'] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
