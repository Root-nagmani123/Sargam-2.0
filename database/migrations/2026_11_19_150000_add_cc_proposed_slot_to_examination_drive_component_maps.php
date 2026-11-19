<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Course Coordinator's proposed exam slot, per component (COE feedback, Nov 2026).
 *
 * The faculty's Tentative Exam Range stays in exam_start_at / exam_end_at (and exam_date /
 * exam_end_date). On approval the CC confirms each component's slot here; once set, this one
 * decides when the exam is over, the venue date and slot clashes. Nullable: every drive
 * approved before this, and every drive not yet approved, has none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examination_drive_component_maps', function (Blueprint $table) {
            if (! Schema::hasColumn('examination_drive_component_maps', 'proposed_start_at')) {
                $table->dateTime('proposed_start_at')->nullable()->after('exam_end_at');
            }
            if (! Schema::hasColumn('examination_drive_component_maps', 'proposed_end_at')) {
                $table->dateTime('proposed_end_at')->nullable()->after('proposed_start_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('examination_drive_component_maps', function (Blueprint $table) {
            foreach (['proposed_end_at', 'proposed_start_at'] as $column) {
                if (Schema::hasColumn('examination_drive_component_maps', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
