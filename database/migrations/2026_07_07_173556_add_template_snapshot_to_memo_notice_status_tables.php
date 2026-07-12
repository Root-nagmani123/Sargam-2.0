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
        // Freezes the template's display fields (title/content/director/signature) at
        // send time, so editing a template later doesn't change what an OT already sees
        // for a notice/memo/discipline-memo already sent to them. memo_notice_template_pk
        // only pins *which* template row was used, not its content at that moment.
        Schema::table('discipline_memo_status', function (Blueprint $table) {
            if (! Schema::hasColumn('discipline_memo_status', 'template_snapshot')) {
                $table->json('template_snapshot')->nullable()->after('memo_notice_template_pk');
            }
        });

        Schema::table('student_notice_status', function (Blueprint $table) {
            if (! Schema::hasColumn('student_notice_status', 'template_snapshot')) {
                $table->json('template_snapshot')->nullable()->after('memo_notice_template_pk');
            }
        });

        Schema::table('student_memo_status', function (Blueprint $table) {
            if (! Schema::hasColumn('student_memo_status', 'template_snapshot')) {
                $table->json('template_snapshot')->nullable()->after('memo_notice_template_pk');
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
        Schema::table('discipline_memo_status', function (Blueprint $table) {
            if (Schema::hasColumn('discipline_memo_status', 'template_snapshot')) {
                $table->dropColumn('template_snapshot');
            }
        });

        Schema::table('student_notice_status', function (Blueprint $table) {
            if (Schema::hasColumn('student_notice_status', 'template_snapshot')) {
                $table->dropColumn('template_snapshot');
            }
        });

        Schema::table('student_memo_status', function (Blueprint $table) {
            if (Schema::hasColumn('student_memo_status', 'template_snapshot')) {
                $table->dropColumn('template_snapshot');
            }
        });
    }
};
