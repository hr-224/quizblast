<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizCreatePageTest extends TestCase
{
    use RefreshDatabase;

    private function html(): string
    {
        $host = User::create(['name' => 'Host', 'email' => 'create@test.com', 'password' => 'secret-pass']);
        return $this->actingAs($host)->get(route('quizzes.create'))->assertOk()->getContent();
    }

    public function test_form_fields_and_submit_are_present(): void
    {
        $html = $this->html();
        foreach (['name="title"', 'name="description"', 'name="is_public"', 'action="' . route('quizzes.store') . '"'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_no_inline_styles(): void
    {
        $src = file_get_contents(resource_path('views/quizzes/create.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src);
    }
}
