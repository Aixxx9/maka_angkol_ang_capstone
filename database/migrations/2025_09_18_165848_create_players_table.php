<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('players', function (Blueprint $table) {
$table->id();
$table->foreignId('team_id')->constrained()->cascadeOnDelete();
$table->string('first_name');
$table->string('last_name');
$table->date('dob')->nullable();
$table->string('position')->nullable(); // varies by sport
$table->integer('number')->nullable();
$table->string('photo_path')->nullable();
$table->text('bio')->nullable();
$table->timestamps();
});
}
public function down(): void { Schema::dropIfExists('players'); }
};