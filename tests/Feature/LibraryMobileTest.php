<?php

namespace Tests\Feature;

use Tests\TestCase;

class LibraryMobileTest extends TestCase
{
    /** Library search form uses responsive CSS classes */
    public function test_library_search_form_has_responsive_classes(): void
    {
        $response = $this->get(route('library'));

        $response->assertStatus(200);
        $response->assertSee('class="library-search-form"', false);
        $response->assertSee('class="form-control library-search-input"', false);
        $response->assertSee('class="library-filter-row"', false);
        $response->assertSee('class="form-control library-search-select"', false);
    }

    /** Library search form has no fixed-width inline styles */
    public function test_library_search_form_has_no_inline_width_styles(): void
    {
        $response = $this->get(route('library'));

        $response->assertStatus(200);
        $response->assertDontSee('style="width:220px"', false);
        $response->assertDontSee('style="width:150px"', false);
        $response->assertDontSee('display:flex;gap:0.5rem', false);
    }

    /** Library search CSS rules are present in app.css */
    public function test_library_search_css_rules_present(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        $this->assertStringContainsString('.library-search-form', $css);
        $this->assertStringContainsString('.library-filter-row', $css);
        $this->assertStringContainsString('.library-search-input', $css);
        $this->assertStringContainsString('max-width: 767px', $css);
    }
}
