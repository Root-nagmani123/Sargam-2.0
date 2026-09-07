<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comments / feedback left on an OT (participant) from the OT / Participants List.
 *
 * Nothing in the existing schema fits this: student_medical_exemption_comments is
 * tied to a medical exemption row, supporting_faculty_feedback / topic_feedback /
 * tt_facultyfeedbackdtls are all session-and-faculty feedback, and
 * discipline_memo_status derives its "notify OT" decision from status rather than
 * storing it — while this feature has to SHOW Notify OT as its own column.
 *
 * Column names follow the conventions already used across this database: `pk` as
 * the key, `*_pk` foreign keys, `active_inactive` for soft removal, and
 * created_date / modified_date timestamps.
 *
 * comment_by_name is a snapshot on purpose. The author is also kept as a user_id,
 * but a faculty record can be renamed or deactivated later and an old comment
 * should still show who actually wrote it.
 */
return new class extends Migration
{
    private const TABLE = 'ot_participant_comment';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->bigIncrements('pk');

            // The OT the comment is about. Not a DB-level FK: student_master is
            // referenced by plain int columns everywhere else in this schema.
            $table->bigInteger('student_master_pk');

            // Which course's list it was written from — context only, nullable
            // because a participant can be reached without a course filter.
            $table->unsignedBigInteger('course_master_pk')->nullable();

            $table->text('message');

            // The modal's Notify OT radio. 1 = Yes (also sends the notification).
            $table->tinyInteger('notify_ot')->default(1);

            // Author: user_credentials.user_id, plus the name shown at the time.
            $table->bigInteger('comment_by_user_id')->nullable();
            $table->string('comment_by_name', 255)->nullable();

            $table->date('comment_date');

            $table->tinyInteger('active_inactive')->default(1);
            $table->bigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            // The list page counts comments per student for a whole page of rows,
            // and the detail page reads one student's newest first.
            $table->index(['student_master_pk', 'active_inactive'], 'idx_opc_student');
            $table->index('comment_date', 'idx_opc_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
