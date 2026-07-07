<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_masters', function (Blueprint $table) {
            // Drop the old single-column unique index on username
            $table->dropUnique('student_masters_username_unique');

            // Add composite unique so one student can have one row per form
            $table->unique(['username', 'form_id'], 'student_masters_username_form_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('student_masters', function (Blueprint $table) {
            $table->dropUnique('student_masters_username_form_id_unique');
            $table->unique('username', 'student_masters_username_unique');
        });
    }
};
