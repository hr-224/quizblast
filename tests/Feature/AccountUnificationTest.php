<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountUnificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_stat_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['total_score', 'games_played', 'wins']));
    }

    public function test_game_players_points_at_users_not_player_accounts(): void
    {
        $this->assertTrue(Schema::hasColumn('game_players', 'user_id'));
        $this->assertFalse(Schema::hasColumn('game_players', 'player_account_id'));
    }

    public function test_player_accounts_table_is_gone(): void
    {
        $this->assertFalse(Schema::hasTable('player_accounts'));
    }
}
