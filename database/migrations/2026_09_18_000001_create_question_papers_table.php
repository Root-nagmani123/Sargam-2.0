<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Examination - question paper assignment and current state.
 *
 * One row per component that needs a written paper. The grain is the component,
 * not the subject: a subject such as Public Administration can carry a written
 * exam alongside an assignment and an iGOT assessment, and only the written one
 * needs a paper. examination_drive_component_maps.source is what separates them.
 *
 * Foreign keys to the Examination Drive tables are added in a later migration
 * once that module has settled; the columns and indexes are here now so this
 * module can be built and tested without waiting on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_papers')) {
            return;
        }

        Schema::create('question_papers', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Denormalised from the component map so drive-wide listings and the
            // progress figure do not have to join through it on every query.
            $table->unsignedBigInteger('examination_drive_id');
            $table->unsignedBigInteger('drive_component_map_id');

            // The paper setter. Taken from examination_drive_faculty_maps at
            // assignment time and copied here, because that mapping is subject
            // level and may be re-pointed later - a paper must keep the faculty
            // who actually uploaded and froze it.
            $table->unsignedBigInteger('faculty_master_pk');

            $table->date('deadline')->nullable();

            // App\Support\COE\QuestionPaperStatus
            $table->tinyInteger('status')->default(0);

            $table->unsignedBigInteger('frozen_by')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            // One paper per component. Re-assigning a faculty member updates the
            // existing row rather than creating a second paper for the same slot.
            $table->unique('drive_component_map_id', 'qp_component_unique');

            $table->index('examination_drive_id');
            $table->index('faculty_master_pk');
            $table->index('status');
            $table->index(['examination_drive_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_papers');
    }
};
