<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Times for a drive component's Tentative Exam Range (COE feedback, Nov 2026).
 *
 * The range is now picked as date + time on the faculty setup page. exam_date / exam_end_date
 * stay (date-only screens and Venue Allocation read them) and are filled from these. When set,
 * these decide when the exam is over (marks entry and results). Nullable: a row saved before
 * this keeps NULL and is treated as its dates, whole days, until the setup page is saved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examination_drive_component_maps', function (Blueprint $table) {
            if (! Schema::hasColumn('examination_drive_component_maps', 'exam_start_at')) {
                $table->dateTime('exam_start_at')->nullable()->after('exam_end_date');
            }
            if (! Schema::hasColumn('examination_drive_component_maps', 'exam_end_at')) {
                $table->dateTime('exam_end_at')->nullable()->after('exam_start_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('examination_drive_component_maps', function (Blueprint $table) {
            foreach (['exam_end_at', 'exam_start_at'] as $column) {
                if (Schema::hasColumn('examination_drive_component_maps', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
