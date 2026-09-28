<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropForeign(['player_account_id']);
            $table->dropColumn('player_account_id');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('is_spectator')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->foreignId('player_account_id')->nullable()->after('is_spectator')->constrained('player_accounts')->nullOnDelete();
        });
    }
};
