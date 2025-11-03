<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('competition_id')->nullable()->after('sport_id')
                ->constrained('competitions')->nullOnDelete();
            $table->unsignedSmallInteger('round')->nullable()->after('competition_id');
            $table->unsignedSmallInteger('bracket_pos')->nullable()->after('round');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->string('name')->default('Tournament')->after('id');
            $table->foreignId('sport_id')->nullable()->after('name')->constrained()->nullOnDelete();
            $table->enum('bracket_type', ['single_elimination'])->default('single_elimination')->after('sport_id');
            $table->enum('status', ['upcoming', 'ongoing', 'completed'])->default('upcoming')->after('bracket_type');
            $table->string('season')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_id');
            $table->dropColumn(['round', 'bracket_pos']);
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sport_id');
            $table->dropColumn(['name', 'bracket_type', 'status', 'season']);
        });
    }
};

