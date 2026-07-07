<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('fc_ot_details', 'consultation_required')) {
            return;
        }

        Schema::table('fc_ot_details', function (Blueprint $table) {
            $table->boolean('consultation_required')->default(false)->after('status')
                ->comment('Doctor marked consultation needed after review');
            $table->timestamp('consultation_required_at')->nullable()->after('consultation_required');
            $table->string('consultation_marked_by', 64)->nullable()->after('consultation_required_at');

            $table->index(['consultation_required', 'course'], 'fc_ot_details_consultation_course_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('fc_ot_details', 'consultation_required')) {
            return;
        }

        Schema::table('fc_ot_details', function (Blueprint $table) {
            $table->dropIndex('fc_ot_details_consultation_course_idx');
            $table->dropColumn([
                'consultation_required',
                'consultation_required_at',
                'consultation_marked_by',
            ]);
        });
    }
};
