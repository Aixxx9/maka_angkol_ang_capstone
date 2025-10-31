<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('highlights', function (Blueprint $table) {
$table->id();
$table->foreignId('game_id')->constrained()->cascadeOnDelete();
$table->string('title');
$table->text('description')->nullable();
$table->string('video_url'); // YT/Vimeo link
$table->timestamps();
});
}
public function down(): void { Schema::dropIfExists('highlights'); }
};