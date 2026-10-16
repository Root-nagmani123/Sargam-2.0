<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('examination_drive_component_maps')) {
            return;
        }

        Schema::create('examination_drive_component_maps', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('examination_drive_id');
            $table->unsignedBigInteger('subject_master_pk');
            $table->unsignedBigInteger('component_master_pk');

            $table->decimal('max_marks', 6, 2);
            $table->decimal('passing_marks', 6, 2);
            $table->string('source', 30)->default('Faculty');
            $table->decimal('weightage', 5, 2);

            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('examination_drive_id')->references('id')->on('examination_drives')->cascadeOnDelete();
            $table->foreign('subject_master_pk')->references('pk')->on('subject_master')->restrictOnDelete();
            $table->foreign('component_master_pk')->references('pk')->on('component_master')->restrictOnDelete();

            // A component can only be mapped once per subject within one drive.
            $table->unique(['examination_drive_id', 'subject_master_pk', 'component_master_pk'], 'ed_component_map_unique');
            $table->index('examination_drive_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_drive_component_maps');
    }
};
