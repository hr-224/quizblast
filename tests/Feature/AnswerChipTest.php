<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AnswerChipTest extends TestCase
{
    public function test_correct_chip_shows_a_check_mark_and_the_class(): void
    {
        $html = Blade::render('<x-answer-chip :text="$t" :correct="true" />', ['t' => 'Paris']);
        $this->assertStringContainsString('class="ans-chip is-correct"', $html);
        $this->assertStringContainsString('✓', $html);
        $this->assertStringContainsString('Paris', $html);
    }

    public function test_incorrect_chip_has_no_check_mark_or_correct_class(): void
    {
        $html = Blade::render('<x-answer-chip :text="$t" :correct="false" />', ['t' => 'London']);
        $this->assertStringContainsString('class="ans-chip"', $html);
        $this->assertStringNotContainsString('is-correct', $html);
        $this->assertStringNotContainsString('✓', $html);
    }

    public function test_text_is_escaped_not_rendered_as_html(): void
    {
        $html = Blade::render('<x-answer-chip :text="$t" :correct="false" />', ['t' => '<b>bold</b>']);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>bold</b>', $html);
    }

    public function test_extra_attributes_merge_onto_the_span(): void
    {
        $html = Blade::render('<x-answer-chip :text="$t" :correct="false" class="extra" />', ['t' => 'X']);
        $this->assertMatchesRegularExpression('/class="ans-chip[^"]*extra[^"]*"/', $html);
    }
}
