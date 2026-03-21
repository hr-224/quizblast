<?php

namespace Tests\Feature;

use Tests\TestCase;

class QuizBlastFixesTest extends TestCase
{
    public function test_api_state_rejects_non_numeric_pin(): void
    {
        $response = $this->get('/api/game/abcdef/state');
        $response->assertStatus(404);
    }

    public function test_api_state_rejects_wrong_length_pin(): void
    {
        $response = $this->get('/api/game/12345/state');
        $response->assertStatus(404);
    }

    public function test_api_state_accepts_numeric_pin(): void
    {
        // Valid 6-digit PIN but non-existent game — should get 404 from controller, not route rejection
        $response = $this->get('/api/game/999999/state');
        // Should reach the controller and return JSON 404 (game not found), not route 404
        $response->assertStatus(404);
        $response->assertJson(['error' => 'Game not found']);
    }

    public function test_api_players_rejects_non_numeric_pin(): void
    {
        $response = $this->get('/api/game/abcdef/players');
        $response->assertStatus(404);
    }

    public function test_api_players_accepts_numeric_pin(): void
    {
        $response = $this->get('/api/game/999999/players');
        $response->assertStatus(404);
        $response->assertJson(['error' => 'Game not found']);
    }
}
