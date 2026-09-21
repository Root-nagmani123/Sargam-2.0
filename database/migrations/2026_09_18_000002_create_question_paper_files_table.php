<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Examination - the uploaded files behind a question paper.
 *
 * A paper is four kinds of file (paper, answer key, instructions, supporting)
 * in one or more languages, so each upload is its own row rather than a column
 * on question_papers.
 *
 * The `language` column is what keeps the requirement "do not overwrite the
 * original": a Hindi translation is a new row with language = 'HI', and the
 * English original stays exactly as the faculty froze it.
 *
 * Replacing a file supersedes the old row (is_current = 0) instead of deleting
 * it, so the history of a paper that was unfrozen and corrected stays intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_paper_files')) {
            return;
        }

        Schema::create('question_paper_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_paper_id');

            // QUESTION_PAPER | ANSWER_KEY | INSTRUCTIONS | SUPPORTING
            $table->string('file_type', 20);

            // config('coe.question_paper.languages') - 'EN' is the original.
            $table->string('language', 5)->default('EN');

            // What the user called it, shown on download. Never used to build a
            // path: uploaded filenames are caller-supplied and are not trusted.
            $table->string('original_name', 255);

            // System-generated path under the private disk.
            $table->string('stored_path', 500);

            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // SHA-256 of the stored bytes. A question paper is a confidential
            // document; the hash is what proves the file served at exam time is
            // the file that was frozen.
            $table->string('file_hash', 64)->nullable();

            $table->unsignedInteger('version_no')->default(1);
            $table->boolean('is_current')->default(true);

            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('question_paper_id')
                ->references('id')->on('question_papers')
                ->cascadeOnDelete();

            $table->index(['question_paper_id', 'is_current']);
            $table->index(['question_paper_id', 'language', 'file_type'], 'qpf_paper_lang_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_paper_files');
    }
};
