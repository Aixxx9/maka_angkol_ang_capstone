<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoreboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('left_school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('right_school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained('sports')->cascadeOnDelete();
            $table->string('match_label')->nullable();
            $table->unsignedInteger('left_score')->default(0);
            $table->unsignedInteger('right_score')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoreboards');
    }
};

