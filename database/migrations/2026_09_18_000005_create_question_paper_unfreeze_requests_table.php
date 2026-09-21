<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Examination - a faculty request to reopen a frozen question paper.
 *
 * Freezing is meant to be final, so the only way back is an explicit request
 * with a stated reason that an authorised officer approves. The reason is
 * mandatory and kept permanently: it is the record of why a locked paper was
 * opened, which is the question asked if a paper is later disputed.
 *
 * Rejections are kept too - a rejected request is part of the paper's history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_paper_unfreeze_requests')) {
            return;
        }

        Schema::create('question_paper_unfreeze_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_paper_id');

            $table->unsignedBigInteger('requested_by');
            $table->text('reason');
            $table->timestamp('requested_at')->useCurrent();

            // 0=Pending, 1=Approved, 2=Rejected
            $table->tinyInteger('status')->default(0);

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approver_remarks')->nullable();

            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('question_paper_id')
                ->references('id')->on('question_papers')
                ->cascadeOnDelete();

            // Multiple requests over a paper's life are expected (freeze,
            // correct, freeze, correct again), so no unique key here. The
            // controller allows only one row in status 0 at a time.
            $table->index(['question_paper_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_paper_unfreeze_requests');
    }
};
