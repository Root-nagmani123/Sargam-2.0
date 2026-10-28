<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE › Master Data › Service Passing Marks: the passing marks of each
 * service, one row per service_master row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_passing_marks_master')) {
            return;
        }

        Schema::create('service_passing_marks_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->unsignedBigInteger('service_master_pk');
            $table->decimal('passing_marks', 6, 2);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('service_master_pk')->references('pk')->on('service_master')->restrictOnDelete();

            // One passing-marks value per service.
            $table->unique('service_master_pk');
            // Listing page filters/sorts by status.
            $table->index('active_inactive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_passing_marks_master');
    }
};
