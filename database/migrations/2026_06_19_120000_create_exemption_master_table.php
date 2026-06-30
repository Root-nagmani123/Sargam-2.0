<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exemption_master', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('course_master_pk');
            $table->date('effective_from');
            $table->enum('gender', ['Male', 'Female']);
            $table->decimal('exemption_days', 5, 1)->default(0);
            $table->tinyInteger('active_inactive')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->dateTime('modified_date')->nullable();

            $table->unique(['course_master_pk', 'effective_from', 'gender'], 'exemption_master_course_date_gender_unique');
            $table->index(['course_master_pk', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exemption_master');
    }
};
