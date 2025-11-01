<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });
    }
};

