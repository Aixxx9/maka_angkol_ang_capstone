<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disable flag on users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_disabled')) {
                $table->boolean('is_disabled')->default(false)->after('password');
            }
        });

        // Pivot table for user <-> sports assignments
        if (!Schema::hasTable('sport_user')) {
            Schema::create('sport_user', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sport_id')->constrained('sports')->cascadeOnDelete();
                $table->primary(['user_id', 'sport_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sport_user')) {
            Schema::drop('sport_user');
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_disabled')) {
                $table->dropColumn('is_disabled');
            }
        });
    }
};

