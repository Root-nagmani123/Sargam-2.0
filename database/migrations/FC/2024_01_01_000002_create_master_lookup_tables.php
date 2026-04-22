<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Session Master (e.g. FC-2024, FC-2025)
        Schema::create('session_masters', function (Blueprint $table) {
            $table->id();
            $table->string('session_name', 100);
            $table->string('session_code', 20)->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        // Service Master (IAS, IPS, IFS, IRS, etc.)
        // Schema::create('service_masters', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('service_name', 100);
        //     $table->string('service_code', 20)->unique();
        //     $table->timestamps();
        // });

        // State Master
        // Schema::create('state_masters', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('state_name', 100);
        //     $table->string('state_code', 10)->unique();
        //     $table->timestamps();
        // });

        // State District Master
        Schema::create('state_district_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('state_id');
            $table->string('district_name', 100);
            $table->timestamps();
            $table->foreign('state_id')->references('id')->on('state_masters');
        });

        // Country Master
        // Schema::create('country_masters', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('country_name', 100);
        //     $table->string('country_code', 10)->unique();
        //     $table->timestamps();
        // });

        // Category Master (GEN, OBC, SC, ST, EWS)
        // Schema::create('category_masters', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('category_name', 50);
        //     $table->string('category_code', 10)->unique();
        //     $table->timestamps();
        // });

        // Religion Master
        Schema::create('religion_masters', function (Blueprint $table) {
            $table->id();
            $table->string('religion_name', 100);
            $table->timestamps();
        });

        // Qualification Master
        // Schema::create('qualification_masters', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('qualification_name', 150);
        //     $table->timestamps();
        // });

        // Highest Stream Master
        // Schema::create('highest_stream_masters', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('stream_name', 100);
        //     $table->timestamps();
        // });

        // Board Name Master
        // Schema::create('board_name_masters', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('board_name', 150);
        //     $table->timestamps();
        // });

        // Language Master
        Schema::create('language_master', function (Blueprint $table) {
            $table->id();
            $table->string('language_name', 100);
            $table->timestamps();
        });

        // Job Type Master
        Schema::create('job_type_masters', function (Blueprint $table) {
            $table->id();
            $table->string('job_type_name', 100);
            $table->timestamps();
        });

        // // Father Profession
        // Schema::create('father_professions', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('profession_name', 100);
        //     $table->timestamps();
        // });

        // Institute Master
        Schema::create('institu_masters', function (Blueprint $table) {
            $table->id();
            $table->string('institute_name', 200);
            $table->string('institute_code', 20)->nullable();
            $table->timestamps();
        });

        // Subject Master
        Schema::create('subject_masters', function (Blueprint $table) {
            $table->id();
            $table->string('subject_name', 150);
            $table->timestamps();
        });

        // Travel Type Master
        Schema::create('travel_type_masters', function (Blueprint $table) {
            $table->id();
            $table->string('travel_type_name', 100);
            $table->timestamps();
        });

        // ECM Master (Entry Check Master)
        Schema::create('ecm_masters', function (Blueprint $table) {
            $table->id();
            $table->string('ecm_name', 100);
            $table->timestamps();
        });

        // Vision Statement
        Schema::create('vision_statements', function (Blueprint $table) {
            $table->id();
            $table->text('statement');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        // Cloth Size Master
        Schema::create('student_cloth_size_masters', function (Blueprint $table) {
            $table->id();
            $table->string('size_label', 20);
            $table->timestamps();
        });

        // Shoes Size Master
        Schema::create('student_shoes_size_masters', function (Blueprint $table) {
            $table->id();
            $table->string('size_label', 20);
            $table->timestamps();
        });

        // Sports Master
        Schema::create('sports_masters', function (Blueprint $table) {
            $table->id();
            $table->string('sport_name', 100);
            $table->timestamps();
        });

        // MCTP Travel Mode Master
        Schema::create('mctp_travel_mode_masters', function (Blueprint $table) {
            $table->id();
            $table->string('travel_mode_name', 100);
            $table->timestamps();
        });

        // Pick Up Drop Type Master
        Schema::create('pick_up_drop_type_masters', function (Blueprint $table) {
            $table->id();
            $table->string('type_name', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pick_up_drop_type_masters');
        Schema::dropIfExists('mctp_travel_mode_masters');
        Schema::dropIfExists('sports_masters');
        Schema::dropIfExists('student_shoes_size_masters');
        Schema::dropIfExists('student_cloth_size_masters');
        Schema::dropIfExists('vision_statements');
        Schema::dropIfExists('ecm_masters');
        Schema::dropIfExists('travel_type_masters');
        Schema::dropIfExists('subject_masters');
        Schema::dropIfExists('institu_masters');
        // Schema::dropIfExists('father_professions');
        Schema::dropIfExists('job_type_masters');
        Schema::dropIfExists('language_master');
        // Schema::dropIfExists('board_name_masters');
        Schema::dropIfExists('highest_stream_masters');
        // Schema::dropIfExists('qualification_masters');
        // Schema::dropIfExists('religion_masters');
        // Schema::dropIfExists('category_masters');
        // Schema::dropIfExists('country_masters');
        Schema::dropIfExists('state_district_masters');
        // Schema::dropIfExists('state_masters');
        // Schema::dropIfExists('service_masters');
        Schema::dropIfExists('session_masters');
    }
};
