<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryOverhaulTest extends TestCase
{
    use RefreshDatabase;

    private int $pin = 500000;

    private function host(): User
    {
        return User::firstOrCreate(['email' => 'libhost@test.com'], ['name' => 'Libby', 'password' => 'secret-pass']);
    }

    private function quiz(string $title, array $attrs = [], int $questions = 1): Quiz
    {
        $quiz = Quiz::create(array_merge([
            'user_id' => $this->host()->id, 'title' => $title, 'is_public' => true,
        ], $attrs));
        for ($i = 0; $i < $questions; $i++) {
            Question::create(['quiz_id' => $quiz->id, 'question_text' => "Q$i", 'time_limit' => 20, 'points' => 1000, 'order' => $i]);
        }
        return $quiz;
    }

    private function play(Quiz $quiz, string $status = 'finished', int $times = 1): void
    {
        for ($i = 0; $i < $times; $i++) {
            Game::create(['quiz_id' => $quiz->id, 'user_id' => $this->host()->id, 'pin' => (string) $this->pin++, 'status' => $status]);
        }
    }

    private function titlesInOrder(string $html, array $titles): array
    {
        $positions = [];
        foreach ($titles as $t) {
            $positions[$t] = strpos($html, $t);
        }
        asort($positions);
        return array_keys($positions);
    }

    public function test_popular_sort_orders_by_finished_games_only(): void
    {
        $a = $this->quiz('Alpha Quiz');
        $b = $this->quiz('Bravo Quiz');
        $c = $this->quiz('Charlie Quiz');
        $this->play($a, 'finished', 1);
        $this->play($b, 'finished', 3);
        $this->play($c, 'waiting', 9); // unfinished games never count

        $html = $this->get(route('library', ['sort' => 'popular']))->assertOk()->getContent();
        $grid = substr($html, strpos($html, 'lib-results'));

        $this->assertSame(['Bravo Quiz', 'Alpha Quiz', 'Charlie Quiz'], $this->titlesInOrder($grid, ['Alpha Quiz', 'Bravo Quiz', 'Charlie Quiz']));
    }

    public function test_questions_sort_orders_by_question_count(): void
    {
        $this->quiz('Short One', [], 1);
        $this->quiz('Long One', [], 5);
        $this->quiz('Mid One', [], 3);

        $html = $this->get(route('library', ['sort' => 'questions']))->assertOk()->getContent();
        $grid = substr($html, strpos($html, 'lib-results'));

        $this->assertSame(['Long One', 'Mid One', 'Short One'], $this->titlesInOrder($grid, ['Short One', 'Long One', 'Mid One']));
    }

    public function test_unknown_sort_falls_back_to_newest(): void
    {
        $this->quiz('Older Quiz');
        $this->quiz('Newer Quiz');

        $html = $this->get(route('library', ['sort' => 'bogus; drop table']))->assertOk()->getContent();
        $grid = substr($html, strpos($html, 'lib-results'));

        $this->assertSame(['Newer Quiz', 'Older Quiz'], $this->titlesInOrder($grid, ['Older Quiz', 'Newer Quiz']));
    }

    public function test_cards_show_play_counts_only_when_played(): void
    {
        $played = $this->quiz('Played Quiz');
        $this->quiz('Fresh Quiz');
        $this->play($played, 'finished', 2);

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertStringContainsString('2 plays', $html);
        $this->assertStringNotContainsString('0 plays', $html);
    }

    public function test_category_chips_carry_counts_and_are_ordered_by_size(): void
    {
        $this->quiz('S1', ['category' => 'Science']);
        $this->quiz('S2', ['category' => 'Science']);
        $this->quiz('S3', ['category' => 'Science']);
        $this->quiz('H1', ['category' => 'History']);
        $this->quiz('H2', ['category' => 'History']);
        $this->quiz('G1', ['category' => 'Geography']);

        $html = $this->get(route('library'))->assertOk()->getContent();
        $nav = substr($html, strpos($html, 'lib-cats'), 2500);

        $this->assertSame(['Science', 'History', 'Geography'], $this->titlesInOrder($nav, ['Geography', 'Science', 'History']));
        $this->assertMatchesRegularExpression('/Science\s*<span[^>]*>3<\/span>/', $nav);
        $this->assertStringContainsString('aria-current="page"', $nav); // "All" is active by default
    }

    public function test_chip_links_keep_search_and_sort(): void
    {
        $this->quiz('S1', ['category' => 'Science']);

        $html = $this->get(route('library', ['search' => 'S1', 'sort' => 'popular']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/href="[^"]*category=Science[^"]*"/', $html);
        $chip = preg_match('/href="([^"]*category=Science[^"]*)"/', $html, $m) ? html_entity_decode($m[1]) : '';
        $this->assertStringContainsString('search=S1', $chip);
        $this->assertStringContainsString('sort=popular', $chip);
    }

    public function test_browse_mode_shows_popular_strip_and_category_rows(): void
    {
        $sci = $this->quiz('Sci Quiz', ['category' => 'Science']);
        $this->quiz('Sci Two', ['category' => 'Science']);
        $this->quiz('Hist Quiz', ['category' => 'History']);
        $this->play($sci, 'finished', 2);

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertStringContainsString('lib-featured', $html);
        $this->assertStringContainsString('Popular', $html);
        $this->assertStringContainsString('lib-row', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*category=Science[^"]*"[^>]*>\s*See all/', $html);
    }

    public function test_single_quiz_categories_do_not_get_a_row(): void
    {
        $this->quiz('Lonely Quiz', ['category' => 'History']);

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertStringNotContainsString('class="lib-row"', $html);
        $this->assertStringContainsString('History', $html); // still reachable via its chip
    }

    public function test_featured_strip_falls_back_to_newest_when_nothing_was_played(): void
    {
        $this->quiz('Brand New Quiz');

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertStringContainsString('lib-featured', $html);
        $this->assertStringNotContainsString('Popular', $html);
        $this->assertStringContainsString('Newest', $html);
    }

    public function test_filtered_modes_hide_the_browse_sections(): void
    {
        $this->quiz('Sci Quiz', ['category' => 'Science']);

        foreach (['?category=Science', '?search=Sci', '?sort=popular', '?sort=new&page=2'] as $qs) {
            $html = $this->get('/library' . $qs)->assertOk()->getContent();
            $this->assertStringNotContainsString('lib-featured', $html, $qs);
            $this->assertStringNotContainsString('class="lib-row"', $html, $qs);
        }
    }

    public function test_host_button_goes_to_game_start_for_logged_in_users(): void
    {
        $quiz = $this->quiz('Hostable Quiz');

        $html = $this->actingAs($this->host())->get(route('library'))->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('game.start', $quiz) . '"', $html);
        $this->assertStringContainsString('Host this quiz', $html);
    }

    public function test_guests_get_a_host_button_that_leads_to_signup(): void
    {
        $quiz = $this->quiz('Hostable Quiz');

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('game.start', $quiz), $html);
        $this->assertStringContainsString('Host this quiz', $html);
        $this->assertStringContainsString('href="' . route('register') . '"', $html);
    }

    public function test_every_card_links_to_its_preview(): void
    {
        $quiz = $this->quiz('Previewable Quiz');

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('library.show', $quiz) . '"', $html);
        $this->assertStringContainsString('Preview', $html);
    }

    public function test_cards_never_nest_links(): void
    {
        $this->quiz('One');
        $this->quiz('Two', ['category' => 'Science']);

        $html = $this->actingAs($this->host())->get(route('library'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*>(?:(?!<\/a>).)*?<a\b/s', $html);
    }

    public function test_card_covers_pair_color_with_a_shape(): void
    {
        $this->quiz('Covered Quiz', ['category' => 'Science']);

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/lib-cover lib-cover-[0-3]/', $html);
        $this->assertStringContainsString('ans-shape', $html);
    }

    public function test_private_quizzes_are_never_listed(): void
    {
        $this->quiz('Secret Quiz', ['is_public' => false]);
        $this->quiz('Open Quiz');

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Secret Quiz', $html);
        $this->assertStringContainsString('Open Quiz', $html);
    }

    public function test_empty_library_shows_the_empty_state(): void
    {
        $this->get(route('library'))->assertOk()->assertSee('No quizzes found');
    }

    public function test_quiz_card_component_has_no_inline_styles(): void
    {
        $src = file_get_contents(resource_path('views/components/quiz-card.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src);
    }

    public function test_category_chips_wrap_instead_of_hiding_behind_a_scroll(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertMatchesRegularExpression('/\.lib-cats\{[^}]*flex-wrap:wrap/', $css);
        $this->assertDoesNotMatchRegularExpression('/\.lib-cats\{[^}]*overflow-x/', $css);
    }

    public function test_popular_heading_uses_the_same_spaced_header_as_the_other_sections(): void
    {
        $this->quiz('Brand New Quiz');

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<div class="lib-row-head">\s*<h2 class="lib-section-title" id="lib-featured-h">/', $html);
    }
}
