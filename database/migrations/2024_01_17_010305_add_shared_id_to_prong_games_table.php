<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prong_games', function (Blueprint $table) {
            $table->unsignedBigInteger('shared_id')->after('game_id');
        });
    }

    public function down(): void
    {
        Schema::table('prong_games', function (Blueprint $table) {
            $table->dropColumn('shared_id');
        });
    }
};
