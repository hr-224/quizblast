<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function makePublicQuiz(): Quiz
    {
        $host = User::create(['name' => 'Host', 'email' => 'lib@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Public Quiz', 'is_public' => true, 'category' => 'Science']);
        $q = Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Q?', 'time_limit' => 20, 'points' => 1000, 'order' => 0]);
        Answer::create(['question_id' => $q->id, 'answer_text' => '<b>bold</b>', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $q->id, 'answer_text' => 'plain', 'is_correct' => false, 'order' => 1]);
        return $quiz;
    }

    public function test_library_still_passes_its_mobile_regressions(): void
    {
        $this->makePublicQuiz();
        $html = $this->get(route('library'))->assertOk()->getContent();
        foreach (['class="library-search-form"', 'class="form-control library-search-input"', 'class="library-filter-row"', 'class="form-control library-search-select"'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertStringNotContainsString('style="width:220px"', $html);
        $this->assertStringNotContainsString('style="width:150px"', $html);
        $this->assertStringNotContainsString('display:flex;gap:0.5rem', $html);
        $css = file_get_contents(public_path('css/app.css'));
        foreach (['.library-search-form', '.library-filter-row', '.library-search-input', 'max-width: 767px'] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }

    public function test_library_show_uses_answer_chip_and_escapes_html(): void
    {
        $quiz = $this->makePublicQuiz();
        $html = $this->get(route('library.show', $quiz))->assertOk()->getContent();
        $this->assertStringContainsString('class="ans-chip is-correct"', $html);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $html);
    }

    public function test_no_inline_styles_on_library_views(): void
    {
        foreach (['library/index.blade.php', 'library/show.blade.php'] as $file) {
            $src = file_get_contents(resource_path('views/' . $file));
            $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src, $file);
        }
    }

    public function test_quiz_card_link_stretches_card_to_equal_height(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('.quiz-card-link .quiz-card{height:100%}', $css);
    }

    public function test_pagination_css_override_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('.pagination', $css);
    }

    public function test_pagination_renders_real_page_links_and_preserves_query_string(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'libpage@test.com', 'password' => 'secret-pass']);
        for ($i = 0; $i < 19; $i++) {
            Quiz::create(['user_id' => $host->id, 'title' => "Public Quiz {$i}", 'is_public' => true, 'category' => 'Science']);
        }
        $html = $this->get(route('library', ['category' => 'Science']))->assertOk()->getContent();
        $this->assertStringContainsString('class="pagination"', $html);
        $this->assertMatchesRegularExpression('/<a[^>]*href="[^"]*page=2[^"]*"[^>]*>\s*2\s*<\/a>/', $html);
        // withQueryString() must carry the category filter onto the page-2 link
        $this->assertMatchesRegularExpression('/<a[^>]*href="[^"]*category=Science[^"]*page=2[^"]*"|<a[^>]*href="[^"]*page=2[^"]*category=Science[^"]*"/', $html);
    }
}
