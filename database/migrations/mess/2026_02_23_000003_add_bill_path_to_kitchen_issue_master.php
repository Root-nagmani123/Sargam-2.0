<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_issue_master', 'bill_path')) {
                $table->string('bill_path', 500)->nullable()->after('remarks')->comment('Uploaded bill PDF/Image path');
            }
        });
    }

    public function down()
    {
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_issue_master', 'bill_path')) {
                $table->dropColumn('bill_path');
            }
        });
    }
};
