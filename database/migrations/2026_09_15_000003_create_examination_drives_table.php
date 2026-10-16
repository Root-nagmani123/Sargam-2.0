<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('examination_drives')) {
            return;
        }

        Schema::create('examination_drives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('examination_type_master_pk');
            $table->unsignedBigInteger('term_master_pk');
            $table->unsignedBigInteger('course_master_pk');
            // Fixed list (Phase-I..Phase-IV), validated in the controller — no dedicated master table.
            $table->string('phase', 20);
            $table->year('academic_session');
            $table->date('start_date');
            $table->date('end_date');
            // 0=Draft, 1=Published, 2=Closed
            $table->tinyInteger('status')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('examination_type_master_pk')->references('pk')->on('examination_type_master')->restrictOnDelete();
            $table->foreign('term_master_pk')->references('pk')->on('term_master')->restrictOnDelete();
            $table->foreign('course_master_pk')->references('pk')->on('course_master')->restrictOnDelete();

            $table->index('academic_session');
            $table->index('status');
            $table->index(['course_master_pk', 'academic_session']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_drives');
    }
};
