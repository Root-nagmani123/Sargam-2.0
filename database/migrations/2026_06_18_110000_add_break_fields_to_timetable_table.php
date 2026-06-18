<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Break details for the redesigned Add Event form's Schedule section:
     *  - break_type        : tea | lunch | snacks
     *  - break_start_time  : break window start (HH:MM)
     *  - break_end_time    : break window end (HH:MM)
     *
     * Complements the existing is_break flag (which stays as a quick "has break"
     * indicator). All nullable so events without a break save cleanly.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('timetable', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable', 'break_type')) {
                $table->string('break_type', 20)->nullable()->comment('tea | lunch | snacks');
            }
            if (!Schema::hasColumn('timetable', 'break_start_time')) {
                $table->string('break_start_time', 20)->nullable()->comment('Break start time HH:MM');
            }
            if (!Schema::hasColumn('timetable', 'break_end_time')) {
                $table->string('break_end_time', 20)->nullable()->comment('Break end time HH:MM');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('timetable', function (Blueprint $table) {
            foreach (['break_type', 'break_start_time', 'break_end_time'] as $column) {
                if (Schema::hasColumn('timetable', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
