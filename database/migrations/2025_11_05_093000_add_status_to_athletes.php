<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            if (!Schema::hasColumn('athletes', 'status')) {
                $table->string('status')->default('approved')->after('avatar_path'); // approved|pending|rejected
            }
            if (!Schema::hasColumn('athletes', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            if (Schema::hasColumn('athletes', 'submitted_by')) {
                $table->dropConstrainedForeignId('submitted_by');
            }
            if (Schema::hasColumn('athletes', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

