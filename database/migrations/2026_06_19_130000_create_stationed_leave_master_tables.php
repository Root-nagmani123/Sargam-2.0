<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stationed_leave_master', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('course_master_pk');
            $table->date('effective_from');
            $table->tinyInteger('is_faculty_approval_required')->default(1);
            $table->tinyInteger('active_inactive')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->dateTime('modified_date')->nullable();

            $table->unique(['course_master_pk', 'effective_from'], 'stationed_leave_master_course_date_unique');
        });

        Schema::create('stationed_leave_faculty_approver', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('stationed_leave_master_pk');
            $table->unsignedBigInteger('faculty_master_pk');
            $table->tinyInteger('is_approval_authority')->default(0);
            $table->dateTime('created_date')->nullable();
            $table->dateTime('modified_date')->nullable();

            $table->unique(
                ['stationed_leave_master_pk', 'faculty_master_pk'],
                'stationed_leave_faculty_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stationed_leave_faculty_approver');
        Schema::dropIfExists('stationed_leave_master');
    }
};
