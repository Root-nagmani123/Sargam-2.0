<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE › Master Data › Grade Master: a grade, its classification, the percentage
 * range it covers and whether that range is a pass. Schema agreed with the DBA.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coe_grade_master')) {
            return;
        }

        Schema::create('coe_grade_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->string('grade', 10);
            $table->string('classification', 100);
            $table->decimal('min_percent', 5, 2);
            $table->decimal('max_percent', 5, 2);
            $table->enum('result_status', ['Pass', 'Not Qualified']);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            // One row per grade.
            $table->unique('grade');
            // Listing page filters/sorts by status.
            $table->index('active_inactive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coe_grade_master');
    }
};
