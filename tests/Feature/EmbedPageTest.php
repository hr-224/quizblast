<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmbedPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_embed_page_has_code_and_link(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'embed@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Embed Quiz']);

        $html = $this->actingAs($host)->get(route('quizzes.embed', $quiz))->assertOk()->getContent();

        $this->assertStringContainsString('id="embed-code"', $html);
        $this->assertStringContainsString('id="direct-link"', $html);
        $this->assertStringContainsString('LIVE_PIN', $html);
    }

    public function test_no_inline_styles(): void
    {
        $src = file_get_contents(resource_path('views/quizzes/show.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src);
    }
}
