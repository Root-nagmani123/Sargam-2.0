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
        Schema::table('student_notice_status', function (Blueprint $table) {
            if (! Schema::hasColumn('student_notice_status', 'memo_notice_template_pk')) {
                // Notice template chosen at send time (pinned for rendering).
                $table->unsignedBigInteger('memo_notice_template_pk')->nullable()->after('notice_memo');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_notice_status', function (Blueprint $table) {
            if (Schema::hasColumn('student_notice_status', 'memo_notice_template_pk')) {
                $table->dropColumn('memo_notice_template_pk');
            }
        });
    }
};
