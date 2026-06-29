<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_nature_master', function (Blueprint $table) {
            $table->id('pk');
            $table->string('leave_type', 30);
            $table->string('nature_name', 150);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->tinyInteger('active_inactive')->default(1);
            $table->dateTime('created_date')->nullable();
            $table->dateTime('modified_date')->nullable();
        });

        Schema::create('leave_application', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('course_master_pk');
            $table->unsignedBigInteger('student_master_pk');
            $table->string('leave_type', 30);
            $table->unsignedBigInteger('leave_nature_master_pk')->nullable();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('total_days', 5, 1)->default(0);
            $table->text('reason')->nullable();
            $table->string('contact_number', 15)->nullable();
            $table->tinyInteger('status')->default(0);
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by_faculty_pk')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_remarks')->nullable();
            $table->tinyInteger('active_inactive')->default(1);
            $table->dateTime('created_date')->nullable();
            $table->dateTime('modified_date')->nullable();

            $table->index(['student_master_pk', 'status']);
            $table->index(['course_master_pk', 'leave_type']);
        });

        Schema::create('leave_application_attachment', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('leave_application_pk');
            $table->string('attachment_title', 200)->nullable();
            $table->string('file_path', 500);
            $table->string('original_file_name', 255)->nullable();
            $table->dateTime('created_date')->nullable();
        });

        $now = now();
        $natures = [
            ['leave_type' => 'PT_EXEMPTION', 'nature_name' => 'Medical', 'display_order' => 1],
            ['leave_type' => 'PT_EXEMPTION', 'nature_name' => 'Injury', 'display_order' => 2],
            ['leave_type' => 'PT_EXEMPTION', 'nature_name' => 'Fever', 'display_order' => 3],
            ['leave_type' => 'PT_EXEMPTION', 'nature_name' => 'Other', 'display_order' => 4],
            ['leave_type' => 'STATIONED_LEAVE', 'nature_name' => 'Personal', 'display_order' => 1],
            ['leave_type' => 'STATIONED_LEAVE', 'nature_name' => 'Medical', 'display_order' => 2],
            ['leave_type' => 'STATIONED_LEAVE', 'nature_name' => 'Other', 'display_order' => 3],
        ];

        foreach ($natures as $nature) {
            DB::table('leave_nature_master')->insert(array_merge($nature, [
                'active_inactive' => 1,
                'created_date' => $now,
                'modified_date' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_application_attachment');
        Schema::dropIfExists('leave_application');
        Schema::dropIfExists('leave_nature_master');
    }
};
