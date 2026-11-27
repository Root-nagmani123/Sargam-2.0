<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtc_claims', function (Blueprint $t) {
            $t->id();
            $t->string('claim_no', 32)->unique();
            $t->string('course_id', 64);
            $t->string('course_name');
            $t->unsignedBigInteger('coordinator_user_id')->nullable()->index();
            $t->string('faculty_id', 64);
            $t->string('faculty_name');
            $t->string('faculty_designation')->nullable();
            $t->string('faculty_organisation')->nullable();
            $t->string('faculty_category', 64);
            $t->date('period_from');
            $t->date('period_to');
            $t->decimal('honorarium_rate', 12, 2)->default(0);
            $t->string('travel_mode', 32);
            $t->string('travel_from', 120)->nullable();
            $t->decimal('travel_fare', 12, 2)->default(0);
            $t->decimal('local_conveyance', 12, 2)->default(0);
            $t->unsignedSmallInteger('da_days')->default(0);
            $t->decimal('da_rate', 12, 2)->default(0);
            $t->unsignedSmallInteger('sessions_taken')->default(0);
            $t->decimal('honorarium_amount', 12, 2)->default(0);
            $t->decimal('da_amount', 12, 2)->default(0);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->text('remarks')->nullable();
            $t->string('status', 24)->default('draft')->index();
            $t->unsignedBigInteger('created_by')->index();
            $t->timestamp('forwarded_at')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->decimal('paid_amount', 12, 2)->nullable();
            $t->string('paid_remark', 1000)->nullable();
            $t->unsignedBigInteger('paid_by')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('report_generated_at')->nullable();
            $t->timestamps();

            $t->unique(['course_id', 'faculty_id', 'period_from', 'period_to'], 'gtc_claims_course_faculty_period_unique');
        });

        Schema::create('gtc_claim_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('gtc_claims')->cascadeOnDelete();
            $t->string('source_session_id', 100)->nullable();
            $t->date('session_date');
            $t->time('start_time');
            $t->time('end_time');
            $t->string('topic');
            $t->string('venue', 120)->nullable();
            $t->enum('status', ['taken', 'cancelled'])->default('taken');
            $t->boolean('is_edited')->default(false);
            $t->timestamps();
        });

        Schema::create('gtc_claim_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('gtc_claims')->cascadeOnDelete();
            $t->unsignedBigInteger('user_id');
            $t->string('user_name');
            $t->string('action');
            $t->string('from_status', 24)->nullable();
            $t->string('to_status', 24);
            $t->string('remark', 2000)->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('gtc_claim_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('gtc_claims')->cascadeOnDelete();
            $t->string('original_name');
            $t->string('path');
            $t->string('disk', 32);
            $t->string('mime', 100)->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->unsignedBigInteger('uploaded_by');
            $t->timestamps();
        });

        Schema::create('gtc_user_permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('permission', 32);
            $t->unsignedBigInteger('granted_by')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'permission']);
            $t->index('permission');
        });

        Schema::create('gtc_claim_sequences', function (Blueprint $t) {
            $t->unsignedSmallInteger('year')->primary();
            $t->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtc_claim_sequences');
        Schema::dropIfExists('gtc_user_permissions');
        Schema::dropIfExists('gtc_claim_attachments');
        Schema::dropIfExists('gtc_claim_histories');
        Schema::dropIfExists('gtc_claim_sessions');
        Schema::dropIfExists('gtc_claims');
    }
};
