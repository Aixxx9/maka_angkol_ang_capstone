<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('game_events', function (Blueprint $table) {
$table->id();
$table->foreignId('game_id')->constrained()->cascadeOnDelete();
$table->enum('team', ['home','away']);
$table->string('type'); // score, foul, card, set, etc.
$table->integer('value')->default(0); // points, sets, etc.
$table->json('meta')->nullable(); // flexible per sport
$table->timestamp('occurred_at')->useCurrent();
});
}
public function down(): void { Schema::dropIfExists('game_events'); }
};