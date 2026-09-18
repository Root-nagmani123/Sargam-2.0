<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('examination_drive_faculty_maps')) {
            return;
        }

        Schema::create('examination_drive_faculty_maps', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('examination_drive_id');
            $table->unsignedBigInteger('subject_master_pk');
            $table->unsignedBigInteger('faculty_master_pk');

            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('examination_drive_id')->references('id')->on('examination_drives')->cascadeOnDelete();
            $table->foreign('subject_master_pk')->references('pk')->on('subject_master')->restrictOnDelete();
            $table->foreign('faculty_master_pk')->references('pk')->on('faculty_master')->restrictOnDelete();

            // One faculty assignment per subject within a drive.
            $table->unique(['examination_drive_id', 'subject_master_pk'], 'ed_faculty_map_unique');
            $table->index('examination_drive_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_drive_faculty_maps');
    }
};
