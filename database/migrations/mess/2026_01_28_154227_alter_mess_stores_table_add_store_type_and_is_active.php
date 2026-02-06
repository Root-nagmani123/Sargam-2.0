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
        Schema::table('mess_stores', function (Blueprint $table) {
            if (!Schema::hasColumn('mess_stores', 'store_type')) {
                $table->string('store_type')->default('mess')->after('store_code');
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
        Schema::table('mess_stores', function (Blueprint $table) {
            if (Schema::hasColumn('mess_stores', 'store_type')) {
                $table->dropColumn('store_type');
            }
        });
    }
};
