<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('question_papers', 'unfrozen_at')) {
            return;
        }

        Schema::table('question_papers', function (Blueprint $table) {
            // When COE unfroze the published paper; cleared once the faculty freezes it
            // again or edits the original. While set, the faculty may edit the translation.
            $table->timestamp('unfrozen_at')->nullable()->after('frozen_by');
        });
    }

    public function down(): void
    {
        Schema::table('question_papers', function (Blueprint $table) {
            $table->dropColumn('unfrozen_at');
        });
    }
};
