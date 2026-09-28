<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('player_accounts');
    }

    public function down(): void
    {
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
    }
};
