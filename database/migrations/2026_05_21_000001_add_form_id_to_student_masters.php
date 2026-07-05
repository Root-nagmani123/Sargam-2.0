<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('student_masters', 'form_id')) {
                $table->unsignedBigInteger('form_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_masters', function (Blueprint $table) {
            if (Schema::hasColumn('student_masters', 'form_id')) {
                $table->dropColumn('form_id');
            }
        });
    }
};
