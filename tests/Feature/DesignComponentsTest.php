<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignComponentsTest extends TestCase
{
    private function css(): string
    {
        return file_get_contents(public_path('css/app.css'));
    }

    /** Classes that not-yet-migrated views depend on must keep a rule. */
    public function test_legacy_classes_still_have_rules(): void
    {
        $css = $this->css();
        foreach ([
            '.container', '.container-sm', '.container-md', '.navbar', '.navbar-inner', '.navbar-brand',
            '.navbar-nav', '.nav-link', '.btn', '.btn-primary', '.btn-success', '.btn-danger',
            '.btn-outline', '.btn-white', '.btn-sm', '.btn-lg', '.btn-xl', '.btn-full', '.card',
            '.card-header', '.card-title', '.form-group', '.form-label', '.form-control', '.form-check',
            '.alert', '.alert-success', '.alert-error', '.alert-info', '.field-error', '.page-hero',
            '.quiz-card', '.answer-block', '.leaderboard-item', '.stat-chip', '.timer-bar',
            '.lobby-wrap', '.pu-card', '.review-verdict-card', '.game-topbar',
        ] as $selector) {
            $this->assertMatchesRegularExpression(
                '/(^|[},\s])' . preg_quote($selector, '/') . '[\s,{:.\[]/m',
                $css,
                "{$selector} lost its rule"
            );
        }
    }

    public function test_inputs_use_16px_text_so_ios_does_not_zoom(): void
    {
        $this->assertMatchesRegularExpression('/\.form-control\s*\{[^}]*font-size:\s*1rem/', $this->css());
    }

    public function test_keyboard_focus_is_visible(): void
    {
        $this->assertStringContainsString(':focus-visible', $this->css());
    }

    public function test_calm_and_party_effect_switches_exist(): void
    {
        $css = $this->css();
        $this->assertStringContainsString('html[data-fx="calm"]', $css);
        $this->assertStringContainsString('html[data-fx="party"]', $css);
        $this->assertStringContainsString('.fx-party', $css);
    }

    public function test_buttons_no_longer_use_hard_offset_shadows(): void
    {
        $this->assertStringNotContainsString('box-shadow:0 4px 0', $this->css());
    }

    public function test_components_end_marker_exists_for_later_tasks(): void
    {
        $this->assertStringContainsString('/* === END COMPONENTS === */', $this->css());
    }

    public function test_host_chart_uses_the_new_answer_colors(): void
    {
        // The chart bars are styled by CSS classes (no inline color array in the view any more).
        $css = $this->css();
        foreach (['coral' => '#ff5d73', 'cyan' => '#22d3ee', 'lime' => '#a3e635', 'amber' => '#fbbf24'] as $name => $hex) {
            $this->assertStringContainsString("--ans-{$name}:{$hex}", $css);
        }
        $this->assertStringContainsString('.response-bar{width:100%;border-radius:6px 6px 0 0;background:var(--ans-coral)}', $css);
        $this->assertStringContainsString('.response-bar.ans-3{background:var(--ans-amber)}', $css);
    }
}
