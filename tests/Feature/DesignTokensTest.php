<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    private function css(): string
    {
        return file_get_contents(public_path('css/app.css'));
    }

    private function rootBlock(): string
    {
        preg_match('/:root\s*\{(.*?)\n\}/s', $this->css(), $m);
        return $m[1] ?? '';
    }

    /** @return array<string,string> token => #rrggbb (hex-valued tokens only) */
    private function hexTokens(): array
    {
        preg_match_all('/--([a-z0-9-]+)\s*:\s*(#[0-9a-fA-F]{6})\s*;/', $this->rootBlock(), $rows, PREG_SET_ORDER);
        $out = [];
        foreach ($rows as $row) {
            $out[$row[1]] = strtolower($row[2]);
        }
        return $out;
    }

    private function luminance(string $hex): float
    {
        $rgb = array_map(fn ($h) => hexdec($h) / 255, str_split(ltrim($hex, '#'), 2));
        $rgb = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $rgb);
        return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
    }

    private function contrast(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    public static function textPairs(): array
    {
        return [
            'text on ink'              => ['text', 'ink'],
            'text on surface'          => ['text', 'surface'],
            'text on surface-2'        => ['text', 'surface-2'],
            'muted on ink'             => ['text-muted', 'ink'],
            'muted on surface'         => ['text-muted', 'surface'],
            'muted on surface-2'       => ['text-muted', 'surface-2'],
            'violet text on ink'       => ['violet-text', 'ink'],
            'violet text on surface'   => ['violet-text', 'surface'],
            'white on primary button'  => ['white', 'violet-deep'],
            'white on primary hover'   => ['white', 'violet-hover'],
            'ink on coral tile'        => ['ink', 'ans-coral'],
            'ink on cyan tile'         => ['ink', 'ans-cyan'],
            'ink on lime tile'         => ['ink', 'ans-lime'],
            'ink on amber tile'        => ['ink', 'ans-amber'],
            'ink on success button'    => ['ink', 'ok'],
            'ink on danger button'     => ['ink', 'bad'],
        ];
    }

    #[DataProvider('textPairs')]
    public function test_text_pairs_meet_wcag_aa(string $fg, string $bg): void
    {
        $t = $this->hexTokens();
        $this->assertArrayHasKey($fg, $t, "token --{$fg} missing from :root");
        $this->assertArrayHasKey($bg, $t, "token --{$bg} missing from :root");
        $ratio = $this->contrast($t[$fg], $t[$bg]);
        $this->assertGreaterThanOrEqual(4.5, $ratio, sprintf('%s on %s is %.2f:1', $fg, $bg, $ratio));
    }

    public function test_answer_colors_are_the_specified_values(): void
    {
        $t = $this->hexTokens();
        $this->assertSame('#ff5d73', $t['ans-coral']);
        $this->assertSame('#22d3ee', $t['ans-cyan']);
        $this->assertSame('#a3e635', $t['ans-lime']);
        $this->assertSame('#fbbf24', $t['ans-amber']);
    }

    public function test_legacy_tokens_are_still_defined(): void
    {
        $root = $this->rootBlock();
        foreach ([
            'qb-purple', 'qb-purple2', 'qb-purple3', 'qb-bg', 'qb-darker', 'qb-card', 'qb-surface',
            'qb-border', 'qb-text', 'qb-muted', 'qb-cyan', 'qb-green', 'qb-yellow', 'qb-red',
            'ans-red', 'ans-blue', 'ans-yellow', 'ans-green', 'radius', 'radius-sm', 'radius-lg',
            'transition', 'topbar-h',
        ] as $name) {
            $this->assertMatchesRegularExpression('/--' . preg_quote($name, '/') . '\s*:/', $root, "--{$name} was removed");
        }
    }

    public function test_font_stacks_have_system_fallbacks(): void
    {
        $root = $this->rootBlock();
        $this->assertMatchesRegularExpression("/--font-display\s*:\s*'Lexend'[^;]*system-ui[^;]*sans-serif/", $root);
        $this->assertMatchesRegularExpression("/--font-body\s*:\s*'Atkinson Hyperlegible'[^;]*system-ui[^;]*sans-serif/", $root);
    }

    public function test_stylesheet_avoids_features_old_ipads_cannot_parse(): void
    {
        $css = $this->css();
        foreach (['@layer', ':has(', 'color-mix(', '@container'] as $feature) {
            $this->assertStringNotContainsString($feature, $css, "{$feature} breaks iOS < 15.4");
        }
    }
}
