<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AnswerShapeTest extends TestCase
{
    private const PATHS = [
        'M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 20.9l1.6-7L2 9.2l7.1-.6z',
        'M12 2l8.7 5v10L12 22l-8.7-5V7z',
        'M9 2h6v7h7v6h-7v7H9v-7H2V9h7z',
        'M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z',
    ];

    public function test_component_renders_the_shape_for_each_index(): void
    {
        foreach (self::PATHS as $i => $d) {
            $html = Blade::render('<x-answer-shape :index="' . $i . '" />');
            $this->assertStringContainsString('<svg class="ans-shape"', $html);
            $this->assertStringContainsString('d="' . $d . '"', $html);
            $this->assertStringContainsString('aria-hidden="true"', $html);
        }
    }

    public function test_component_index_wraps_after_four(): void
    {
        $html = Blade::render('<x-answer-shape :index="5" />');
        $this->assertStringContainsString('d="' . self::PATHS[1] . '"', $html);
    }

    public function test_component_negative_index_wraps_like_the_js_twin(): void
    {
        $html = Blade::render('<x-answer-shape :index="-1" />');
        $this->assertStringContainsString('d="' . self::PATHS[3] . '"', $html);
        $html = Blade::render('<x-answer-shape :index="-5" />');
        $this->assertStringContainsString('d="' . self::PATHS[3] . '"', $html);
    }

    public function test_caller_class_merges_into_a_single_class_attribute(): void
    {
        $html = Blade::render('<x-answer-shape :index="0" class="big" />');
        $this->assertSame(1, substr_count($html, 'class='));
        $this->assertMatchesRegularExpression('/class="[^"]*\bans-shape\b[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/class="[^"]*\bbig\b[^"]*"/', $html);
    }

    public function test_js_shapes_match_the_blade_component(): void
    {
        $js = file_get_contents(public_path('js/shapes.js'));
        preg_match_all("/'(M[^']+)'/", $js, $m);
        $this->assertSame(self::PATHS, $m[1]);
    }

    public function test_tile_css_defines_all_four_colors_and_states(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach (['.ans-tile', '.ans-0', '.ans-1', '.ans-2', '.ans-3', '.is-selected', '.is-correct', '.is-wrong', '.is-eliminated', '.is-locked', '.ans-shape'] as $sel) {
            $this->assertStringContainsString($sel, $css, $sel);
        }
    }
}
