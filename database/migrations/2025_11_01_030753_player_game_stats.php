<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_game_stats', function (Blueprint $table) {
    $table->id();
    $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
    $table->integer('points')->default(0);
    $table->integer('rebounds')->default(0);
    $table->integer('assists')->default(0);
    $table->decimal('fg_percent', 5, 2)->default(0);
    $table->date('game_date');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
