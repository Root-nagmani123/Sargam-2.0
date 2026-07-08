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
        Schema::table('discipline_memo_status', function (Blueprint $table) {
            if (! Schema::hasColumn('discipline_memo_status', 'memo_notice_template_pk')) {
                // Template chosen at send time, pinned so 'View Memo' renders exactly that template.
                $table->unsignedBigInteger('memo_notice_template_pk')->nullable()->after('discipline_master_pk');
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
            if (Schema::hasColumn('discipline_memo_status', 'memo_notice_template_pk')) {
                $table->dropColumn('memo_notice_template_pk');
            }
        });
    }
};
