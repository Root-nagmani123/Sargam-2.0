<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Departure / return time for a stationed leave.
     *
     * from_date and to_date only fix the days; a stationed leave also needs the
     * time the officer trainee leaves the station and the time they report back.
     * Both are nullable: every application recorded before this migration has no
     * time, and PT exemptions never carry one (they run for whole PT sessions).
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_application', 'time_from')) {
            return;
        }

        Schema::table('leave_application', function (Blueprint $table) {
            $table->time('time_from')->nullable()->after('to_date');
            $table->time('time_to')->nullable()->after('time_from');
        });
    }

    public function down(): void
    {
        // Deliberately one-way, like 2026_09_03_000000. Once deployed, time_from /
        // time_to are the only copy of every stationed-leave departure and return
        // time (from_date / to_date hold days only), so dropping them would silently
        // delete those times. If this change must be reverted, restore
        // leave_application from a backup instead (PR #334 F-057).
    }
};
