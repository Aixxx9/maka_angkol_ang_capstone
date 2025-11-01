<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_game_stats', function (Blueprint $table) {
            $table->json('metrics')->nullable()->after('fg_percent');
        });
    }

    public function down(): void
    {
        Schema::table('player_game_stats', function (Blueprint $table) {
            $table->dropColumn('metrics');
        });
    }
};

