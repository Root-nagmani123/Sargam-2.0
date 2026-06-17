<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_repository_master', function (Blueprint $table) {
            if (!Schema::hasColumn('course_repository_master', 'category_attachment')) {
                $table->string('category_attachment')->nullable()->after('category_image');
            }
        });
    }

    public function down()
    {
        Schema::table('course_repository_master', function (Blueprint $table) {
            if (Schema::hasColumn('course_repository_master', 'category_attachment')) {
                $table->dropColumn('category_attachment');
            }
        });
    }
};
