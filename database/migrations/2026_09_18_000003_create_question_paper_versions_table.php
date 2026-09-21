<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Examination - a snapshot of a question paper at each lock point.
 *
 * audit_logs records that an action happened; this records what the paper
 * looked like when it happened. Taken on every freeze, unfreeze and finalize,
 * so "which files were frozen on 10-09-2026" can be answered later even after
 * the paper has been corrected and re-frozen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_paper_versions')) {
            return;
        }

        Schema::create('question_paper_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_paper_id');

            $table->unsignedInteger('version_no');

            // The status the paper moved into at this point.
            $table->tinyInteger('status_at_version');

            // The file rows that were current at that moment, as JSON. Kept as a
            // snapshot rather than a join so a later correction cannot change
            // what an earlier version says it contained.
            $table->json('file_snapshot')->nullable();

            // Present when the version was cut by an unfreeze.
            $table->text('reason')->nullable();

            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('created_date')->useCurrent();

            $table->foreign('question_paper_id')
                ->references('id')->on('question_papers')
                ->cascadeOnDelete();

            $table->unique(['question_paper_id', 'version_no'], 'qpv_paper_version_unique');
            $table->index('question_paper_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_paper_versions');
    }
};
