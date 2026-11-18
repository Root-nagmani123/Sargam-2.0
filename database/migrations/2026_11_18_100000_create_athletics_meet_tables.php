<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Athletics Meet: masters (event / venue / house), drives (a nomination window
 * for one course), nominations and results.
 *
 * course_master_pk / student_master_pk point at legacy tables whose key types
 * vary by environment, so they are indexed but not constrained; the athletics
 * tables reference each other with real foreign keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athletics_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('category', 20);       // track | field | group
            $table->string('gender', 10);         // male | female
            $table->string('participation', 20);  // individual | team
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('athletics_venues', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('venue_type', 20);     // track | field | group
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('athletics_houses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('athletics_house_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained('athletics_houses')->cascadeOnDelete();
            $table->unsignedBigInteger('course_master_pk')->index();
            $table->unsignedBigInteger('student_master_pk');
            $table->timestamps();
            // One house per OT per course.
            $table->unique(['course_master_pk', 'student_master_pk'], 'athl_house_member_unique');
        });

        Schema::create('athletics_drives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_master_pk')->index();
            $table->string('form_title', 200);
            $table->string('result_recorded_as', 20);   // time | distance | height | rank
            $table->string('visible_to', 10);           // male | female | both
            $table->string('max_criteria', 10);         // event | overall
            $table->unsignedTinyInteger('max_nominations');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('instructions')->nullable();
            $table->string('status', 10)->default('draft'); // draft | active
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('athletics_drive_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drive_id')->constrained('athletics_drives')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('athletics_events');
            $table->foreignId('venue_id')->constrained('athletics_venues');
            $table->unique(['drive_id', 'event_id']);
        });

        Schema::create('athletics_nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drive_id')->constrained('athletics_drives')->cascadeOnDelete();
            $table->unsignedBigInteger('student_master_pk')->index();
            $table->string('status', 10)->default('pending'); // pending | approved | rejected
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['drive_id', 'student_master_pk']);
        });

        Schema::create('athletics_nomination_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomination_id')->constrained('athletics_nominations')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('athletics_events');
            $table->unique(['nomination_id', 'event_id']);
        });

        Schema::create('athletics_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drive_id')->constrained('athletics_drives')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('athletics_events');
            $table->unsignedSmallInteger('position');
            $table->unsignedBigInteger('student_master_pk')->nullable();
            $table->foreignId('house_id')->nullable()->constrained('athletics_houses')->nullOnDelete();
            $table->string('performance', 30)->nullable();
            $table->decimal('points', 6, 2)->default(0);
            $table->string('remarks', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['drive_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athletics_results');
        Schema::dropIfExists('athletics_nomination_events');
        Schema::dropIfExists('athletics_nominations');
        Schema::dropIfExists('athletics_drive_events');
        Schema::dropIfExists('athletics_drives');
        Schema::dropIfExists('athletics_house_members');
        Schema::dropIfExists('athletics_houses');
        Schema::dropIfExists('athletics_venues');
        Schema::dropIfExists('athletics_events');
    }
};
