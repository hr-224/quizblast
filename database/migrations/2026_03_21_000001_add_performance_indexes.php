<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_answers', function (Blueprint $table) {
            $table->index(['game_id', 'question_id'], 'game_answers_game_question_idx');
            $table->index('game_player_id', 'game_answers_player_idx');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->index('game_id', 'game_players_game_idx');
            $table->index(['game_id', 'is_spectator'], 'game_players_game_spectator_idx');
        });
    }

    public function down(): void
    {
        Schema::table('game_answers', function (Blueprint $table) {
            $table->dropIndex('game_answers_game_question_idx');
            $table->dropIndex('game_answers_player_idx');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->dropIndex('game_players_game_idx');
            $table->dropIndex('game_players_game_spectator_idx');
        });
    }
};
