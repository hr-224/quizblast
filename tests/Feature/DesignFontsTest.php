<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignFontsTest extends TestCase
{
    private const FILES = [
        'lexend-latin', 'lexend-latin-ext',
        'atkinson-400-latin', 'atkinson-400-latin-ext',
        'atkinson-700-latin', 'atkinson-700-latin-ext',
    ];

    private function css(): string
    {
        return file_get_contents(public_path('css/app.css'));
    }

    /** @return string[] every @font-face block in app.css */
    private function fontFaceBlocks(): array
    {
        preg_match_all('/@font-face\s*\{[^}]*\}/', $this->css(), $m);
        return $m[0];
    }

    public function test_font_files_exist_and_are_woff2(): void
    {
        foreach (self::FILES as $name) {
            $path = public_path("fonts/{$name}.woff2");
            $this->assertFileExists($path, $name);
            $this->assertSame('wOF2', substr(file_get_contents($path), 0, 4), "{$name} is not a WOFF2 file");
        }
    }

    public function test_every_font_file_is_declared_with_swap(): void
    {
        $blocks = $this->fontFaceBlocks();
        foreach (self::FILES as $name) {
            $matching = array_filter($blocks, fn ($b) => str_contains($b, "/fonts/{$name}.woff2"));
            $this->assertNotEmpty($matching, "{$name} is not referenced by any @font-face");
            foreach ($matching as $block) {
                $this->assertStringContainsString('font-display:swap', $block);
            }
        }
    }

    public function test_legacy_font_names_are_aliased_to_new_files(): void
    {
        $joined = implode("\n", $this->fontFaceBlocks());
        $this->assertStringContainsString("font-family:'Montserrat'", $joined);
        $this->assertStringContainsString("font-family:'Source Sans 3'", $joined);
    }

    public function test_no_third_party_font_hosts_in_shared_assets(): void
    {
        $files = array_merge(
            [public_path('css/app.css')],
            glob(resource_path('views/layouts/*.php')) ?: [],
            glob(resource_path('views/partials/*.php')) ?: [],
        );
        foreach ($files as $file) {
            $src = file_get_contents($file);
            $this->assertStringNotContainsString('fonts.googleapis.com', $src, $file);
            $this->assertStringNotContainsString('fonts.gstatic.com', $src, $file);
        }
    }
}
