<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizEditorRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuiz(int $questionCount = 2): array
    {
        $host = User::create(['name' => 'Host', 'email' => 'editor@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Editor Quiz']);
        $questions = collect();
        for ($i = 0; $i < $questionCount; $i++) {
            $q = Question::create(['quiz_id' => $quiz->id, 'question_text' => "Q{$i} text", 'time_limit' => 20, 'points' => 1000, 'order' => $i]);
            foreach ([['Alpha', true], ['Bravo', false], ['Charlie', false], ['Delta', false]] as $a => [$text, $correct]) {
                Answer::create(['question_id' => $q->id, 'answer_text' => $text, 'is_correct' => $correct, 'order' => $a]);
            }
            $questions->push($q);
        }
        return compact('host', 'quiz', 'questions');
    }

    private function html(int $questionCount = 2): string
    {
        ['host' => $host, 'quiz' => $quiz] = $this->makeQuiz($questionCount);
        return $this->actingAs($host)->get(route('quizzes.edit', $quiz))->assertOk()->getContent();
    }

    private function src(): string
    {
        return file_get_contents(resource_path('views/quizzes/edit.blade.php'));
    }

    public function test_empty_quiz_shows_the_empty_state_and_add_form(): void
    {
        $html = $this->html(0);
        $this->assertStringContainsString('No questions yet', $html);
        $this->assertStringContainsString('name="question_text"', $html);
        $this->assertStringContainsString('id="questions-sortable"', $html);
    }

    public function test_each_question_renders_a_collapsible_card_with_its_own_form(): void
    {
        ['host' => $host, 'quiz' => $quiz, 'questions' => $qs] = $this->makeQuiz(2);
        $html = $this->actingAs($host)->get(route('quizzes.edit', $quiz))->getContent();

        $this->assertSame(2, substr_count($html, 'q-card mb-2"'));   // one per question card; substring avoids depending on class attribute order
        foreach ($qs as $q) {
            $this->assertStringContainsString('data-id="' . $q->id . '"', $html);
            $this->assertStringContainsString('action="' . route('quizzes.updateQuestion', [$quiz, $q]) . '"', $html);
        }
        $this->assertStringContainsString('<x-answer-shape', $this->src());
        $this->assertSame(8, substr_count($html, 'class="ans-chip'));   // 2 questions × 4 answers, collapsed summary
    }

    public function test_no_edit_modal_or_json_question_data(): void
    {
        $src = $this->src();
        foreach (['id="edit-modal"', 'openEditModal', 'closeEditModal', 'questionData'] as $needle) {
            $this->assertStringNotContainsString($needle, $src, $needle);
        }
    }

    public function test_html_in_question_text_is_shown_literally(): void
    {
        ['host' => $host, 'quiz' => $quiz] = $this->makeQuiz(0);
        Question::create(['quiz_id' => $quiz->id, 'question_text' => '<b>bold</b> question', 'time_limit' => 20, 'points' => 1000, 'order' => 0]);
        $html = $this->actingAs($host)->get(route('quizzes.edit', $quiz))->getContent();
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>bold</b> question', $html);
    }

    public function test_no_inline_styles_or_handlers(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style|\son(click|submit)\s*=/i', $this->src());
    }

    public function test_delete_buttons_use_data_confirm_not_inline_onsubmit(): void
    {
        $src = $this->src();
        $this->assertStringNotContainsString('onsubmit=', $src);
        $this->assertMatchesRegularExpression('/data-confirm="[^"]*[Dd]elete[^"]*"/', $src);
        $this->assertMatchesRegularExpression('/data-confirm="[^"]*[Rr]emove[^"]*"/', $src);
    }

    public function test_script_is_modern_but_avoids_optional_chaining(): void
    {
        $src = $this->src();
        preg_match('/<script>(?!.*qb-confirm).*?<\/script>/s', $src, $m);
        $this->assertStringNotContainsString('?.', $src);
        $this->assertStringNotContainsString('??', $src);
    }

    public function test_every_id_the_script_reads_exists_in_the_markup_or_is_created_by_it(): void
    {
        $src = $this->src();
        preg_match_all("/getElementById\('([^']+)'\)/", $src, $m);
        // Every id read by a literal getElementById call must exist as id="..." somewhere in the file
        // (ids created per-card in the @foreach loop use string concatenation and are exempt from this check).
        foreach (array_unique($m[1]) as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $src, "script uses #{$id} but the markup has no such id");
        }
    }

    public function test_drag_reorder_renumbers_via_dedicated_q_num_span(): void
    {
        ['host' => $host, 'quiz' => $quiz] = $this->makeQuiz(2);
        $html = $this->actingAs($host)->get(route('quizzes.edit', $quiz))->getContent();
        $this->assertStringContainsString('class="q-num"', $html);

        $src = $this->src();
        $this->assertMatchesRegularExpression('/querySelectorAll\(\'\.q-num\'\)/', $src);
        $this->assertStringNotContainsString("querySelectorAll('.q-card-meta')", $src);
        $this->assertStringNotContainsString('textContent.replace', $src);
    }

    public function test_hidden_correct_answer_inputs_are_also_disabled_so_they_do_not_submit(): void
    {
        $src = $this->src();
        $this->assertMatchesRegularExpression(
            "/q-single'\\)\\.forEach\\(el => \\{ el\\.classList\\.toggle\\('hidden', isMulti\\); el\\.disabled = isMulti; \\}\\)/",
            $src
        );
        $this->assertMatchesRegularExpression(
            "/q-multi'\\)\\.forEach\\(el => \\{ el\\.classList\\.toggle\\('hidden', !isMulti\\); el\\.disabled = !isMulti; \\}\\)/",
            $src
        );
    }

    public function test_answer_shape_tiles_get_per_index_color_classes(): void
    {
        $html = $this->html(1);
        foreach (range(0, 3) as $i) {
            $this->assertStringContainsString('class="ans-shape ans-fg-' . $i . '"', $html);
        }
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('.ans-fg-0{color:var(--ans-coral)}', $css);
        $this->assertStringContainsString('.ans-fg-1{color:var(--ans-cyan)}', $css);
        $this->assertStringContainsString('.ans-fg-2{color:var(--ans-lime)}', $css);
        $this->assertStringContainsString('.ans-fg-3{color:var(--ans-amber)}', $css);
    }

    public function test_delete_question_button_has_accessible_name(): void
    {
        $html = $this->html(1);
        $this->assertStringContainsString('aria-label="Delete question"', $html);
    }

    public function test_settings_toggle_has_aria_state_that_flips_with_the_panel(): void
    {
        $html = $this->html(0);
        $this->assertMatchesRegularExpression('/id="settings-toggle"[^>]*aria-expanded="false"[^>]*aria-controls="edit-meta"|id="settings-toggle"[^>]*aria-controls="edit-meta"[^>]*aria-expanded="false"/', $html);

        $src = $this->src();
        $this->assertMatchesRegularExpression('/settingsToggle\.addEventListener\(\'click\', \(\) => \{[^}]*setAttribute\(\'aria-expanded\'/s', $src);
    }

    public function test_cancelling_a_card_returns_focus_to_its_opener(): void
    {
        $src = $this->src();
        $this->assertMatchesRegularExpression('/if \(opener\) \{[^}]*opener\.focus\(\);[^}]*\}|if \(opener\) opener\.focus\(\);/s', $src);
    }

    public function test_reorder_failure_shows_an_alert(): void
    {
        $src = $this->src();
        $this->assertStringContainsString("window.alert('Could not save the new order", $src);
        $this->assertStringContainsString('res.ok', $src);
    }

    public function test_settings_panel_and_header_actions_still_present(): void
    {
        ['host' => $host, 'quiz' => $quiz] = $this->makeQuiz(1);
        $html = $this->actingAs($host)->get(route('quizzes.edit', $quiz))->getContent();
        foreach ([
            'id="edit-meta"', 'name="title"', 'name="category"', 'name="tags"', 'name="is_public"',
            route('game.start', $quiz), route('quizzes.duplicate', $quiz), route('quizzes.embed', $quiz), route('quizzes.destroy', $quiz),
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }
}
