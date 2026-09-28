<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuizBannerTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://cdn.example.com/banner.jpg';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    protected function tearDown(): void
    {
        foreach (glob(public_path('uploads/banners/*')) ?: [] as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function owner(): User
    {
        return User::firstOrCreate(['email' => 'banner@test.com'], ['name' => 'Banner', 'password' => 'secret-pass']);
    }

    private function quiz(array $attrs = []): Quiz
    {
        return Quiz::create(array_merge(['user_id' => $this->owner()->id, 'title' => 'Bannered', 'is_public' => true], $attrs));
    }

    private function image(string $name = 'b.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 900, 300);
    }

    /** A real (non-fake) upload so MIME detection sniffs the content, not the .jpg name. */
    private function disguisedPhp(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'qb');
        file_put_contents($tmp, '<?php system($_GET["c"]);');

        return new UploadedFile($tmp, 'x.jpg', 'image/jpeg', null, true);
    }

    private function files(): array
    {
        return glob(public_path('uploads/banners/*')) ?: [];
    }

    private function update(Quiz $quiz, array $data)
    {
        return $this->actingAs($this->owner())->put(route('quizzes.update', $quiz), array_merge(['title' => $quiz->title], $data));
    }

    // ── Setting a banner ─────────────────────────────────────────────────────

    public function test_create_with_an_uploaded_banner_stores_the_file(): void
    {
        $this->actingAs($this->owner())->post(route('quizzes.store'), ['title' => 'New', 'banner_file' => $this->image()])
            ->assertSessionHasNoErrors();

        $quiz = Quiz::first();
        $this->assertMatchesRegularExpression('#^uploads/banners/[A-Za-z0-9]{40}\.(jpg|jpeg|png|webp)$#', $quiz->banner);
        $this->assertFileExists(public_path($quiz->banner));
        $this->assertSame(asset($quiz->banner), $quiz->banner_url);
    }

    public function test_create_with_a_banner_url_stores_the_url(): void
    {
        $this->actingAs($this->owner())->post(route('quizzes.store'), ['title' => 'New', 'banner_url' => self::URL])
            ->assertSessionHasNoErrors();

        $quiz = Quiz::first();
        $this->assertSame(self::URL, $quiz->banner);
        $this->assertSame(self::URL, $quiz->banner_url);
        $this->assertCount(0, $this->files());
    }

    public function test_an_uploaded_file_wins_over_a_url_when_both_are_sent(): void
    {
        $this->actingAs($this->owner())->post(route('quizzes.store'), [
            'title' => 'New', 'banner_file' => $this->image(), 'banner_url' => self::URL,
        ])->assertSessionHasNoErrors();

        $this->assertStringStartsWith('uploads/banners/', Quiz::first()->banner);
    }

    public function test_quizzes_without_a_banner_have_no_banner_url(): void
    {
        $this->assertNull($this->quiz()->banner_url);
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function test_rejects_files_that_are_not_jpg_png_or_webp(): void
    {
        foreach ([
            UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            $this->disguisedPhp(),
        ] as $bad) {
            $this->actingAs($this->owner())->post(route('quizzes.store'), ['title' => 'New', 'banner_file' => $bad])
                ->assertSessionHasErrors('banner_file');
        }
        $this->assertSame(0, Quiz::count());
        $this->assertCount(0, $this->files());
    }

    public function test_rejects_files_over_two_megabytes(): void
    {
        $this->actingAs($this->owner())->post(route('quizzes.store'), [
            'title' => 'New', 'banner_file' => UploadedFile::fake()->image('big.jpg', 900, 300)->size(2049),
        ])->assertSessionHasErrors('banner_file');
    }

    public function test_rejects_non_http_banner_urls(): void
    {
        foreach (['javascript:alert(1)', 'data:image/png;base64,AAAA', 'ftp://x.com/a.jpg', 'not a url'] as $bad) {
            $this->actingAs($this->owner())->post(route('quizzes.store'), ['title' => 'New', 'banner_url' => $bad])
                ->assertSessionHasErrors('banner_url');
        }
        $this->assertSame(0, Quiz::count());
    }

    // ── Updating / removing ──────────────────────────────────────────────────

    public function test_replacing_an_upload_deletes_the_old_file(): void
    {
        $quiz = $this->quiz();
        $this->update($quiz, ['banner_file' => $this->image('a.jpg')])->assertSessionHasNoErrors();
        $old = $quiz->fresh()->banner;

        $this->update($quiz, ['banner_file' => $this->image('b.png')])->assertSessionHasNoErrors();

        $new = $quiz->fresh()->banner;
        $this->assertNotSame($old, $new);
        $this->assertFileDoesNotExist(public_path($old));
        $this->assertFileExists(public_path($new));
    }

    public function test_switching_from_an_upload_to_a_url_deletes_the_file(): void
    {
        $quiz = $this->quiz();
        $this->update($quiz, ['banner_file' => $this->image()]);
        $old = $quiz->fresh()->banner;

        $this->update($quiz, ['banner_url' => self::URL])->assertSessionHasNoErrors();

        $this->assertSame(self::URL, $quiz->fresh()->banner);
        $this->assertFileDoesNotExist(public_path($old));
    }

    public function test_remove_banner_clears_it_and_deletes_the_file(): void
    {
        $quiz = $this->quiz();
        $this->update($quiz, ['banner_file' => $this->image()]);
        $old = $quiz->fresh()->banner;

        $this->update($quiz, ['remove_banner' => '1'])->assertSessionHasNoErrors();

        $this->assertNull($quiz->fresh()->banner);
        $this->assertFileDoesNotExist(public_path($old));
    }

    public function test_saving_settings_without_banner_fields_keeps_the_banner(): void
    {
        $quiz = $this->quiz(['banner' => self::URL]);

        $this->update($quiz, ['title' => 'Renamed'])->assertSessionHasNoErrors();

        $this->assertSame(self::URL, $quiz->fresh()->banner);
        $this->assertSame('Renamed', $quiz->fresh()->title);
    }

    public function test_a_failed_validation_leaves_the_existing_banner_alone(): void
    {
        $quiz = $this->quiz();
        $this->update($quiz, ['banner_file' => $this->image()]);
        $old = $quiz->fresh()->banner;

        $this->update($quiz, ['banner_url' => 'javascript:alert(1)'])->assertSessionHasErrors('banner_url');

        $this->assertSame($old, $quiz->fresh()->banner);
        $this->assertFileExists(public_path($old));
    }

    public function test_only_the_owner_can_change_a_banner(): void
    {
        $quiz  = $this->quiz();
        $other = User::create(['name' => 'Eve', 'email' => 'eve@test.com', 'password' => 'secret-pass']);

        $this->actingAs($other)->put(route('quizzes.update', $quiz), ['title' => 'Hijack', 'banner_file' => $this->image()])
            ->assertForbidden();

        $this->assertNull($quiz->fresh()->banner);
        $this->assertCount(0, $this->files());
    }

    // ── Cleanup and duplication ──────────────────────────────────────────────

    public function test_deleting_a_quiz_deletes_its_uploaded_banner(): void
    {
        $quiz = $this->quiz();
        $this->update($quiz, ['banner_file' => $this->image()]);
        $path = $quiz->fresh()->banner;

        $this->actingAs($this->owner())->delete(route('quizzes.destroy', $quiz));

        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_a_duplicate_keeps_the_banner_and_the_file_survives_until_the_last_quiz_goes(): void
    {
        $quiz = $this->quiz();
        $this->update($quiz, ['banner_file' => $this->image()]);
        $path = $quiz->fresh()->banner;

        $this->actingAs($this->owner())->post(route('quizzes.duplicate', $quiz));
        $copy = Quiz::where('title', 'Bannered (Copy)')->first();
        $this->assertSame($path, $copy->banner);

        $this->actingAs($this->owner())->delete(route('quizzes.destroy', $quiz));
        $this->assertFileExists(public_path($path)); // the copy still uses it

        $this->actingAs($this->owner())->delete(route('quizzes.destroy', $copy));
        $this->assertFileDoesNotExist(public_path($path));
    }

    // ── Display ──────────────────────────────────────────────────────────────

    public function test_library_card_shows_the_banner_over_the_shape_cover(): void
    {
        $this->quiz(['banner' => self::URL]);

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<img class="lib-cover-img" src="' . preg_quote(self::URL, '#') . '"[^>]*loading="lazy"[^>]*alt=""#', $html);
        $this->assertStringContainsString('referrerpolicy="no-referrer"', $html);
        $this->assertStringContainsString('ans-shape', $html); // shape stays underneath as the fallback
    }

    public function test_library_card_without_a_banner_has_no_cover_image(): void
    {
        $this->quiz();

        $html = $this->get(route('library'))->assertOk()->getContent();

        $this->assertStringNotContainsString('lib-cover-img', $html);
    }

    public function test_show_page_header_displays_the_banner(): void
    {
        $quiz = $this->quiz(['banner' => self::URL]);

        $html = $this->get(route('library.show', $quiz))->assertOk()->getContent();

        $this->assertStringContainsString('class="lib-banner-img"', $html);
        $this->assertStringContainsString(self::URL, $html);
    }

    public function test_show_page_without_a_banner_has_no_banner_block(): void
    {
        $quiz = $this->quiz();

        $this->get(route('library.show', $quiz))->assertOk()->assertDontSee('lib-banner');
    }

    // ── Forms ────────────────────────────────────────────────────────────────

    public function test_create_and_edit_forms_accept_uploads_and_urls(): void
    {
        $quiz = $this->quiz(['banner' => self::URL]);

        foreach ([route('quizzes.create'), route('quizzes.edit', $quiz)] as $url) {
            $html = $this->actingAs($this->owner())->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('enctype="multipart/form-data"', $html, $url);
            $this->assertStringContainsString('name="banner_file"', $html, $url);
            $this->assertStringContainsString('name="banner_url"', $html, $url);
        }
    }

    public function test_edit_form_offers_to_remove_an_existing_banner(): void
    {
        $quiz = $this->quiz(['banner' => self::URL]);

        $this->actingAs($this->owner())->get(route('quizzes.edit', $quiz))
            ->assertSee('name="remove_banner"', false);
    }

    public function test_banner_views_have_no_inline_styles(): void
    {
        foreach (['components/quiz-card', 'library/show', 'quizzes/create', 'quizzes/edit', 'components/banner-fields'] as $view) {
            $path = resource_path("views/{$view}.blade.php");
            if (!file_exists($path)) { continue; }
            $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', file_get_contents($path), $view);
        }
    }
}
