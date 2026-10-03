<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * course_week_notes.faculty_legend_order - the order the "Faculty Abbreviation"
 * box prints its codes in (seniority on the issued sheet, which no master
 * records), e.g. ["BR", "SW", "VL", "BG"]. Guarded like the rest of the
 * timetable schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_week_notes') && !Schema::hasColumn('course_week_notes', 'faculty_legend_order')) {
            Schema::table('course_week_notes', function (Blueprint $table) {
                $table->json('faculty_legend_order')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_week_notes') && Schema::hasColumn('course_week_notes', 'faculty_legend_order')) {
            Schema::table('course_week_notes', function (Blueprint $table) {
                $table->dropColumn('faculty_legend_order');
            });
        }
    }
};
