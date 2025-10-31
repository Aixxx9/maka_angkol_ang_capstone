<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts','category')) $table->string('category')->nullable()->after('title'); // e.g. Basketball, Boxing
            if (!Schema::hasColumn('posts','is_featured')) $table->boolean('is_featured')->default(false)->after('category'); // hero rail picker
            if (!Schema::hasColumn('posts','views_count')) $table->unsignedBigInteger('views_count')->default(0)->after('excerpt');
            if (!Schema::hasColumn('posts','published_at')) $table->timestamp('published_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            foreach (['category','is_featured','views_count','published_at'] as $c) {
                if (Schema::hasColumn('posts', $c)) $table->dropColumn($c);
            }
        });
    }
};
