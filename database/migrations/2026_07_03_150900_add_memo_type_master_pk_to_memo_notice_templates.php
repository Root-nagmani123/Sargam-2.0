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
        Schema::table('memo_notice_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('memo_notice_templates', 'memo_type_master_pk')) {
                // Null = memo-type-agnostic template (fallback when no type-specific one exists).
                // Only meaningful when memo_notice_type = 'Memo'.
                $table->unsignedBigInteger('memo_type_master_pk')->nullable()->after('discipline_master_pk');
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
        Schema::table('memo_notice_templates', function (Blueprint $table) {
            if (Schema::hasColumn('memo_notice_templates', 'memo_type_master_pk')) {
                $table->dropColumn('memo_type_master_pk');
            }
        });
    }
};
