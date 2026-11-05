<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — one participant's marks in one component of a drive subject
 * (Result Management → Faculty Result Entry, read by Result Compilation).
 *
 * ── Why not a FK to examination_drive_component_maps.id ──
 *   ExaminationDriveController::saveComponents() deletes a subject's component rows
 *   and inserts them again on every save, so their ids change. Marks hang off the
 *   key that survives that — (drive, subject, component), which is the unique key
 *   ed_component_map_unique on examination_drive_component_maps — and are joined
 *   back to it on read. Marks of a component taken off the drive stay here unread,
 *   and show again if it is put back.
 *
 * ── Type mapping, checked against the live schema ──
 *   student_master.pk      bigint SIGNED    → bigInteger(), as on coe_participant_codes.
 *   examination_drives.id, subject_master.pk, component_master.pk,
 *   faculty_master.pk      bigint UNSIGNED  → unsignedBigInteger().
 *
 * ── Status ──
 *   1 = Draft (saved by faculty, still editable), 2 = Approved (locked; counted by
 *   Result Compilation). Absent participants have is_absent = 1 and marks NULL.
 */
return new class extends Migration
{
    private const TABLE = 'coe_evaluation_marks';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');

            $table->unsignedBigInteger('examination_drive_id');
            $table->unsignedBigInteger('subject_master_pk');
            $table->unsignedBigInteger('component_master_pk');
            // Signed — matches student_master.pk. See the note above.
            $table->bigInteger('student_master_pk');

            $table->decimal('marks', 6, 2)->nullable();
            $table->tinyInteger('is_absent')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=Draft, 2=Approved');

            $table->unsignedBigInteger('evaluated_by_faculty_pk')->nullable()->comment('faculty_master.pk of who entered it');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('user_credentials.user_id');
            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            $table->unique(
                ['examination_drive_id', 'subject_master_pk', 'component_master_pk', 'student_master_pk'],
                'coe_em_unique'
            );
            $table->index('student_master_pk', 'coe_em_student_index');

            $table->foreign('examination_drive_id', 'coe_em_drive_fk')
                ->references('id')->on('examination_drives')->cascadeOnDelete();
            $table->foreign('subject_master_pk', 'coe_em_subject_fk')
                ->references('pk')->on('subject_master');
            $table->foreign('component_master_pk', 'coe_em_component_fk')
                ->references('pk')->on('component_master');
            $table->foreign('student_master_pk', 'coe_em_student_fk')
                ->references('pk')->on('student_master');
            $table->foreign('evaluated_by_faculty_pk', 'coe_em_faculty_fk')
                ->references('pk')->on('faculty_master')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
