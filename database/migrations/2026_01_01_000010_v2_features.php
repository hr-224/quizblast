<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Questions: image, video, multiple correct
        Schema::table('questions', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('question_text');
            $table->string('video_url')->nullable()->after('image_url');
            $table->boolean('multiple_correct')->default(false)->after('video_url');
        });

        // Quizzes: category, tags
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
            $table->string('tags')->nullable()->after('category');
        });

        // Games: team mode, spectator mode
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('team_mode')->default(false)->after('status');
            $table->boolean('spectator_mode')->default(true)->after('team_mode');
        });

        // Game players: team, streak, power_ups used
        Schema::table('game_players', function (Blueprint $table) {
            $table->string('team')->nullable()->after('score');
            $table->integer('streak')->default(0)->after('team');
            $table->integer('best_streak')->default(0)->after('streak');
            $table->json('power_ups')->nullable()->after('best_streak');
            $table->boolean('is_spectator')->default(false)->after('power_ups');
        });

        // Game answers: was streak active, power up used
        Schema::table('game_answers', function (Blueprint $table) {
            $table->string('power_up_used')->nullable()->after('points_earned');
            $table->integer('streak_bonus')->default(0)->after('power_up_used');
        });

        // Player accounts
        Schema::create('player_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('total_score')->default(0);
            $table->integer('games_played')->default(0);
            $table->integer('wins')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        // Link game_players to player accounts
        Schema::table('game_players', function (Blueprint $table) {
            $table->foreignId('player_account_id')->nullable()->after('is_spectator')->constrained('player_accounts')->nullOnDelete();
        });

        // Emoji reactions
        Schema::create('game_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_reactions');
        Schema::dropIfExists('player_accounts');

        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn(['team','streak','best_streak','power_ups','is_spectator','player_account_id']);
        });
        Schema::table('game_answers', function (Blueprint $table) {
            $table->dropColumn(['power_up_used','streak_bonus']);
        });
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['team_mode','spectator_mode']);
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['category','tags']);
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['image_url','video_url','multiple_correct']);
        });
    }
};
