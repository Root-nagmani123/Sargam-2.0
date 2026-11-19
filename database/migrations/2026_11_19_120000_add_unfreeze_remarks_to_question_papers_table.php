<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('question_papers', 'unfreeze_remarks')) {
            return;
        }

        Schema::table('question_papers', function (Blueprint $table) {
            // Why COE last unfroze the published paper (required on Unfreeze). Each
            // unfreeze is also in question_paper_logs; this holds the latest reason.
            $table->string('unfreeze_remarks', 500)->nullable()->after('unfrozen_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('question_papers', 'unfreeze_remarks')) {
            return;
        }

        Schema::table('question_papers', function (Blueprint $table) {
            $table->dropColumn('unfreeze_remarks');
        });
    }
};
