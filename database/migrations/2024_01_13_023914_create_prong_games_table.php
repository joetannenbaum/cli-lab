<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prong_games', function (Blueprint $table) {
            $table->id();
            $table->string('game_id')->index();
            $table->boolean('player_one')->default(false);
            $table->boolean('player_two')->default(false);
            $table->boolean('player_one_ready')->default(false);
            $table->boolean('player_two_ready')->default(false);
            $table->smallInteger('player_one_position')->nullable();
            $table->smallInteger('player_two_position')->nullable();
            $table->smallInteger('ball_x')->nullable();
            $table->smallInteger('ball_y')->nullable();
            $table->string('ball_direction')->nullable();
            $table->string('ball_speed')->nullable();
            $table->smallInteger('winner')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prong_games');
    }
};
