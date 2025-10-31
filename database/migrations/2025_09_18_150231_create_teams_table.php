<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('teams', function (Blueprint $table) {
$table->id();
$table->foreignId('school_id')->constrained()->cascadeOnDelete();
$table->foreignId('sport_id')->constrained()->cascadeOnDelete();
$table->string('name'); // e.g., PRISAA HS Boys Basketball
$table->string('season')->nullable(); // 2025, etc.
$table->timestamps();
$table->unique(['school_id','sport_id','season']);
});
}
public function down(): void { Schema::dropIfExists('teams'); }
};