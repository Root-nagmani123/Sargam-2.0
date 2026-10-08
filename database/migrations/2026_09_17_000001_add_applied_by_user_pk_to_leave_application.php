<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail for leave recorded by the training section on behalf of an officer
     * trainee. NULL means the officer trainee applied for themselves (every existing
     * row); a user_credentials pk means the training section entered it for them.
     *
     * approved_by_faculty_pk cannot carry this: it is a faculty_master reference and
     * the training-section operator is an employee, not a faculty member.
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_application', 'applied_by_user_pk')) {
            return;
        }

        Schema::table('leave_application', function (Blueprint $table) {
            $table->unsignedBigInteger('applied_by_user_pk')->nullable()->after('rejection_remarks');
            $table->index('applied_by_user_pk', 'leave_application_applied_by_user_pk_index');
        });
    }

    public function down(): void
    {
        // Deliberately one-way, like 2026_09_17_000003. Once deployed,
        // applied_by_user_pk is the only record that the training section entered a
        // leave on an officer trainee's behalf; dropping it would make every such
        // APPROVED stationed leave read as self-applied. If this change must be
        // reverted, restore leave_application from a backup instead (PR #334 F-061).
    }
};
