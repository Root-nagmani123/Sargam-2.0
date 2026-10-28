<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE › Master Data › Subject Max Marks: the maximum marks of each subject,
 * one row per subject_master row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subject_max_marks_master')) {
            return;
        }

        Schema::create('subject_max_marks_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->unsignedBigInteger('subject_master_pk');
            $table->decimal('max_marks', 6, 2);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('subject_master_pk')->references('pk')->on('subject_master')->restrictOnDelete();

            // One max-marks value per subject.
            $table->unique('subject_master_pk');
            // Listing page filters/sorts by status.
            $table->index('active_inactive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_max_marks_master');
    }
};
