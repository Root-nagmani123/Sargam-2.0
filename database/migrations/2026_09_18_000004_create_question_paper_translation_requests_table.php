<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Examination - translation of a frozen question paper.
 *
 * Raised by the Examination Section once the faculty has frozen the original,
 * and worked by the Translation Section. Its own lifecycle is tracked here
 * rather than on question_papers.status because the two move at different
 * speeds: the paper is simply "in translation" while this row walks from
 * assigned to uploaded to frozen to approved.
 *
 * The translated file itself is a question_paper_files row with language = 'HI'.
 * Nothing here touches the original.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_paper_translation_requests')) {
            return;
        }

        Schema::create('question_paper_translation_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_paper_id');

            $table->string('target_language', 5)->default('HI');

            // 0=Pending, 1=In Progress, 2=Uploaded, 3=Frozen, 4=Approved
            $table->tinyInteger('status')->default(0);

            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->useCurrent();

            // Translation Section user this sits with.
            $table->unsignedBigInteger('assigned_to')->nullable();

            $table->timestamp('translated_at')->nullable();
            $table->timestamp('frozen_at')->nullable();

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->text('remarks')->nullable();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('question_paper_id')
                ->references('id')->on('question_papers')
                ->cascadeOnDelete();

            // One live request per paper per language. A paper sent back for
            // re-translation reuses its row instead of stacking duplicates.
            $table->unique(['question_paper_id', 'target_language'], 'qptr_paper_lang_unique');
            $table->index('status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_paper_translation_requests');
    }
};
