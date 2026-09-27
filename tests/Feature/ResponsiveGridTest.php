<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResponsiveGridTest extends TestCase
{
    /**
     * At 390px both the 768px and 1024px grid-3/grid-4 media queries match. CSS source order
     * decides the winner, so the 768px (1-column) rule must be the LAST one declared for
     * .grid-3/.grid-4 — otherwise the later 1024px rule's 2-column layout wins on phones.
     */
    public function test_mobile_one_column_rule_wins_over_tablet_two_column_rule(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        $pos768 = strpos($css, '@media(max-width:768px){.grid-2,.grid-3,.grid-4{grid-template-columns:1fr}}');
        $pos1024 = strpos($css, '@media(max-width:1024px){.grid-3{grid-template-columns:repeat(2,1fr)}.grid-4{grid-template-columns:repeat(2,1fr)}}');

        $this->assertNotFalse($pos768, 'the 768px grid rule must exist verbatim');
        $this->assertNotFalse($pos1024, 'the 1024px grid rule must exist verbatim');
        $this->assertGreaterThan($pos1024, $pos768, 'the 768px (1-column) rule must come AFTER the 1024px (2-column) rule so it wins the cascade on phones');
    }
}
