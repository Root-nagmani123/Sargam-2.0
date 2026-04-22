<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create parent forms table
        Schema::create('fc_forms', function (Blueprint $table) {
            $table->id();
            $table->string('form_name', 150);
            $table->string('form_slug', 80)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('bi-file-text');
            $table->string('consolidation_table', 100)->nullable()->comment('Master table for tracking step completion');
            $table->string('user_identifier', 100)->default('username')->comment('Column name used to identify user rows');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        // Add form_id to existing steps table
        Schema::table('fc_form_steps', function (Blueprint $table) {
            $table->unsignedBigInteger('form_id')->nullable()->after('id');
            $table->foreign('form_id')->references('id')->on('fc_forms')->cascadeOnDelete();
        });

        // Seed the default FC Registration form and link existing steps
        $formId = DB::table('fc_forms')->insertGetId([
            'form_name'           => 'FC Registration',
            'form_slug'           => 'fc-registration',
            'description'         => 'Foundation Course Officer Trainee Registration Form',
            'icon'                => 'bi-person-badge',
            'consolidation_table' => 'student_masters',
            'user_identifier'     => 'username',
            'is_active'           => 1,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Link all existing steps to this form
        DB::table('fc_form_steps')->whereNull('form_id')->update(['form_id' => $formId]);
    }

    public function down(): void
    {
        Schema::table('fc_form_steps', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
            $table->dropColumn('form_id');
        });

        Schema::dropIfExists('fc_forms');
    }
};
