<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('achievements', function (Blueprint $table) {
$table->id();
$table->foreignId('school_id')->constrained()->cascadeOnDelete();
$table->string('title');
$table->year('year')->nullable();
$table->text('details')->nullable();
$table->timestamps();
});
}
public function down(): void { Schema::dropIfExists('achievements'); }
};