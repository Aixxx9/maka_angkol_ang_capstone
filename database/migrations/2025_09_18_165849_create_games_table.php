<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('games', function (Blueprint $table) {
$table->id();
$table->foreignId('sport_id')->constrained()->cascadeOnDelete();
$table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
$table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
$table->dateTime('starts_at');
$table->string('venue')->nullable();
$table->enum('status', ['scheduled','live','final'])->default('scheduled');
$table->unsignedInteger('home_score')->default(0);
$table->unsignedInteger('away_score')->default(0);
$table->string('live_embed_url')->nullable(); // FB or YT
$table->timestamps();
});
}
public function down(): void { Schema::dropIfExists('games'); }
};