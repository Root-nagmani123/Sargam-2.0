<?php

use App\Support\MigrationSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Qualification Details ───────────────────────────────────
        MigrationSchema::createIfMissing('student_master_qualification_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->unsignedBigInteger('qualification_id')->nullable();
            $table->string('degree_name', 200)->nullable();
            $table->unsignedBigInteger('board_id')->nullable();
            $table->string('institution_name', 300)->nullable();
            $table->string('year_of_passing', 10)->nullable();
            $table->string('percentage_cgpa', 20)->nullable();
            $table->unsignedBigInteger('stream_id')->nullable();
            $table->string('subject_details', 500)->nullable();
            $table->timestamps();
        });

        // ─── Higher Educational Details ──────────────────────────────
        MigrationSchema::createIfMissing('student_master_higher_educational_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('degree_type', 100)->nullable();           // PG / PhD / PG Diploma etc.
            $table->string('subject_name', 200)->nullable();
            $table->string('university_name', 300)->nullable();
            $table->string('year_of_passing', 10)->nullable();
            $table->string('percentage_cgpa', 20)->nullable();
            $table->timestamps();
        });

        // ─── Employment Details ──────────────────────────────────────
        MigrationSchema::createIfMissing('student_master_employment_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('organisation_name', 300)->nullable();
            $table->string('designation', 200)->nullable();
            $table->unsignedBigInteger('job_type_id')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->tinyInteger('is_current')->default(0);
            $table->timestamps();

            $table->foreign('job_type_id')->references('id')->on('job_type_masters')->nullOnDelete();
        });

        // ─── Spouse Details ──────────────────────────────────────────
        MigrationSchema::createIfMissing('student_master_spouse_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('spouse_name', 200)->nullable();
            $table->date('spouse_dob')->nullable();
            $table->string('spouse_occupation', 200)->nullable();
            $table->string('spouse_organisation', 300)->nullable();
            $table->string('no_of_children', 10)->nullable();
            $table->string('children_details', 500)->nullable();
            $table->timestamps();
        });

        // ─── Language Known ──────────────────────────────────────────
        MigrationSchema::createIfMissing('student_master_language_knowns', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->unsignedBigInteger('language_id')->nullable();
            $table->tinyInteger('can_read')->default(0);
            $table->tinyInteger('can_write')->default(0);
            $table->tinyInteger('can_speak')->default(0);
            $table->string('proficiency', 50)->nullable();            // Basic / Intermediate / Fluent
            $table->timestamps();

            $table->foreign('language_id')->references('id')->on('language_master')->nullOnDelete();
        });

        // ─── Hindi Knowledge ─────────────────────────────────────────
        MigrationSchema::createIfMissing('student_knowledge_hindi_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('medium_of_study', 100)->nullable();
            $table->tinyInteger('hindi_medium_school')->default(0);
            $table->tinyInteger('hindi_subject_studied')->default(0);
            $table->string('highest_hindi_exam', 150)->nullable();
            $table->timestamps();
        });

        // ─── Hobbies & Skills ────────────────────────────────────────
        MigrationSchema::createIfMissing('student_master_hobbies_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->text('hobbies')->nullable();
            $table->text('special_skills')->nullable();
            $table->text('extra_curricular')->nullable();
            $table->timestamps();
        });

        // ─── Skill Details ───────────────────────────────────────────
        MigrationSchema::createIfMissing('student_skill_details_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('skill_name', 200)->nullable();
            $table->string('skill_level', 100)->nullable();
            $table->string('year_acquired', 10)->nullable();
            $table->timestamps();
        });

        // ─── Academic Distinction ────────────────────────────────────
        MigrationSchema::createIfMissing('student_master_academic_distinctions', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('distinction_type', 200)->nullable();       // Gold Medal / Rank / Prize etc.
            $table->string('description', 500)->nullable();
            $table->string('year', 10)->nullable();
            $table->string('awarding_body', 200)->nullable();
            $table->timestamps();
        });

        // ─── Sports Fitness (Coach/Teacher) ──────────────────────────
        MigrationSchema::createIfMissing('student_sports_fitness_teach_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->unsignedBigInteger('sport_id')->nullable();
            $table->string('level', 100)->nullable();                  // National / State / District
            $table->string('role', 100)->nullable();                   // Player / Coach
            $table->string('year', 10)->nullable();
            $table->timestamps();

            $table->foreign('sport_id')->references('id')->on('sports_masters')->nullOnDelete();
        });

        // ─── Sports Training (Coach/Teacher) ─────────────────────────
        MigrationSchema::createIfMissing('student_sports_trg_teach_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->unsignedBigInteger('sport_id')->nullable();
            $table->string('training_institute', 300)->nullable();
            $table->string('duration', 100)->nullable();
            $table->string('year', 10)->nullable();
            $table->timestamps();

            $table->foreign('sport_id')->references('id')->on('sports_masters')->nullOnDelete();
        });

        // ─── Module Details ──────────────────────────────────────────
        MigrationSchema::createIfMissing('student_master_module_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('chosen_module', 100)->nullable();
            $table->string('second_module', 100)->nullable();
            $table->timestamps();
        });

        // ─── Student Exempted (Medical/Physical exemption) ───────────
        MigrationSchema::createIfMissing('student_master_exempted_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->tinyInteger('is_exempted')->default(0);
            $table->string('exemption_reason', 500)->nullable();
            $table->string('doc_path', 500)->nullable();
            $table->timestamps();
        });

        // ─── FC Scale Master (scale of pay etc.) ─────────────────────
        MigrationSchema::createIfMissing('student_fc_scale_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('pay_level', 50)->nullable();
            $table->string('basic_pay', 50)->nullable();
            $table->string('grade_pay', 50)->nullable();
            $table->timestamps();
        });

        // ─── Student Confirm Master ───────────────────────────────────
        MigrationSchema::createIfMissing('student_confirm_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->tinyInteger('declaration_accepted')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // ─── Registration Status (incomplete tracking) ────────────────
        MigrationSchema::createIfMissing('student_master_incomplet_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('incomplete_reason', 500)->nullable();
            $table->string('incomplete_fields', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_master_incomplet_masters');
        Schema::dropIfExists('student_confirm_masters');
        Schema::dropIfExists('student_fc_scale_masters');
        Schema::dropIfExists('student_master_exempted_masters');
        Schema::dropIfExists('student_master_module_masters');
        Schema::dropIfExists('student_sports_trg_teach_masters');
        Schema::dropIfExists('student_sports_fitness_teach_masters');
        Schema::dropIfExists('student_master_academic_distinctions');
        Schema::dropIfExists('student_skill_details_masters');
        Schema::dropIfExists('student_master_hobbies_details');
        Schema::dropIfExists('student_knowledge_hindi_masters');
        Schema::dropIfExists('student_master_language_knowns');
        Schema::dropIfExists('student_master_spouse_masters');
        Schema::dropIfExists('student_master_employment_details');
        Schema::dropIfExists('student_master_higher_educational_details');
        Schema::dropIfExists('student_master_qualification_details');
    }
};
