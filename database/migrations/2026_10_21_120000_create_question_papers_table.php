<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_papers')) {
            return;
        }

        Schema::create('question_papers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('examination_drive_id');
            $table->unsignedBigInteger('subject_master_pk');
            $table->unsignedBigInteger('faculty_master_pk')->nullable();

            $table->string('paper_code', 30)->unique();
            $table->string('paper_name', 200);
            $table->date('deadline')->nullable();

            // Faculty's paper: an uploaded .docx or the editor's HTML, on the private 'local' disk.
            $table->string('original_file_path')->nullable();
            $table->string('original_file_name')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamp('original_uploaded_at')->nullable();
            $table->text('comments')->nullable();

            // Rajbhasha upload; translated_by is user_credentials.pk
            $table->string('translated_file_path')->nullable();
            $table->unsignedBigInteger('translated_by')->nullable();
            $table->timestamp('translated_at')->nullable();

            // Values double as CSS classes in the views (coe-state--pending-faculty) - keep the hyphens.
            $table->string('status', 30)->default('pending-faculty');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->foreign('examination_drive_id')->references('id')->on('examination_drives')->cascadeOnDelete();
            $table->foreign('subject_master_pk')->references('pk')->on('subject_master')->restrictOnDelete();
            $table->foreign('faculty_master_pk')->references('pk')->on('faculty_master')->nullOnDelete();

            // One paper per subject within a drive. Keyed on drive + subject, not on the
            // faculty map row id: the drive's update() deletes and recreates those rows.
            $table->unique(['examination_drive_id', 'subject_master_pk'], 'qp_drive_subject_unique');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_papers');
    }
};
