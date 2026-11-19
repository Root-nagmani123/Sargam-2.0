<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Course Coordinator's proposed exam start / end carry a time (COE feedback, Nov 2026).
 *
 * Only proposed_start_date / proposed_end_date change, DATE -> DATETIME; existing values become
 * midnight. The drive's own start_date / end_date stay DATE (read across COE) and still take
 * the date part on approval (ExaminationDriveController::ccApprove). Raw ALTER: ->change()
 * needs doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['proposed_start_date', 'proposed_end_date'] as $column) {
            if (Schema::hasColumn('examination_drives', $column)) {
                DB::statement("ALTER TABLE examination_drives MODIFY {$column} DATETIME NULL");
            }
        }
    }

    public function down(): void
    {
        // Times are dropped; the dates are kept.
        foreach (['proposed_start_date', 'proposed_end_date'] as $column) {
            if (Schema::hasColumn('examination_drives', $column)) {
                DB::statement("ALTER TABLE examination_drives MODIFY {$column} DATE NULL");
            }
        }
    }
};
